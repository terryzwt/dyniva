<?php

namespace Drupal\dyniva_connect\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityManager;
use Drupal\dyniva_connect\Entity\Connector;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Drupal\Core\Session\AccountInterface;
use Drupal\dyniva_connect\Entity\Connection;
use Symfony\Component\HttpFoundation\Response;
use Drupal\user\Entity\User;
use Drupal\Component\Utility\SafeMarkup;
use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Symfony\Component\HttpFoundation\File\Exception\AccessDeniedException;
use Drupal\Component\Utility\Crypt;
use Drupal\Core\Site\Settings;

/**
 * Class ConnectController.
 *
 * @package Drupal\dyniva_connect\Controller
 */
class ConnectController extends ControllerBase {
  
  /**
   * Drupal\Core\Entity\EntityManager definition.
   *
   * @var Drupal\Core\Entity\EntityManager
   */
  protected $entity_manager;

  /**
   *
   * {@inheritdoc}
   */
  public function __construct(EntityManager $entity_manager) {
    $this->entity_manager = $entity_manager;
  }

  /**
   *
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('entity.manager'));
  }

  /**
   * Connectadmin.
   *
   * @return string Return Hello string.
   */
  public function connectAdmin() {
    return [
      '#type' => 'markup',
      '#markup' => $this->t('Implement method: connectAdmin')
    ];
  }

  /**
   * 用户认证入口
   *
   * @param Connector $connector
   * @return string[]|\Drupal\Core\StringTranslation\TranslatableMarkup[]
   */
  public function connect(Connector $connector = null) {
    
    \Drupal::service('page_cache_kill_switch')->trigger();
    
    if (!$connector) {
      $connector = dyniva_connect_get_default_connector();
    }
    if (!$connector) {
      throw new AccessDeniedHttpException();
    }
    if($destination = \Drupal::request()->get('destination')){
      $_SESSION['destination'] = $destination;
      \Drupal::request()->query->remove('destination');
    }
    $current = \Drupal::currentUser();
    if($uid = \Drupal::request()->get('uid')){
      $account = user_load($uid);
      $timestamp = \Drupal::request()->get('timestamp');
      $token = \Drupal::request()->get('token');
      if(Crypt::hashEquals($token, user_pass_rehash($account, $timestamp))){
        user_login_finalize($account);
      }
    }
    
    try {
      /** @var Drupal\dyniva_connect\Entity\Connection $connection **/
      $connection = \Drupal::service('plugin.manager.connector_type_plugin')->processConnect($connector);
      
      if ($connection) {
        if ($connection instanceof Response) {
          if($connection instanceof RedirectResponse){
            return new TrustedRedirectResponse($connection->getTargetUrl());
          }
          return $connection;
        }
        if ($connection->getOwnerId() > 0) {
          $account = $connection->getOwner();
          if ($current->isAnonymous()) {
            user_login_finalize($account);
          }
          \Drupal::queue('sync_connection_user_picture')->createItem($connection);
          $url = $this->getUserLoginDestination($account);
          return new TrustedRedirectResponse($url);
        }
        else {
          return $this->redirect('dyniva_connect.bind_form', array(
            'connector' => $connector->id(),
            'connection' => $connection->id()
          ));
        }
      }
    }
    catch (\Exception $e) {
      watchdog_exception('dyniva_connect', $e);
    }
    throw new AccessDeniedHttpException();
  }

  public function getUserLoginDestination(AccountInterface $account) {
    $url = '/';
    if (isset($_SESSION['destination'])) {
      $url = $_SESSION['destination'];
      unset($_SESSION['destination']);
    }
    elseif (isset($_GET['destination'])) {
      $url = $_GET['destination'];
    }
    return $url;
  }

  /**
   * 消息处理入口
   *
   * @param Connector $connector
   * @return string[]|\Drupal\Core\StringTranslation\TranslatableMarkup[]
   */
  public function message(Connector $connector = null) {
    
    \Drupal::service('page_cache_kill_switch')->trigger();
    
    if (!$connector) {
      $connector = dyniva_connect_get_default_connector();
    }
    if (!$connector) {
      throw new AccessDeniedHttpException();
    }
    
    $return = \Drupal::service('plugin.manager.connector_type_plugin')->processMessage($connector);
    
    return $return;
  }

  /**
   * User page.
   */
  public function userPage(User $user) {
    
    $rows = [];
    $connectors = \Drupal::entityTypeManager()->getStorage('connector')->loadMultiple();
    foreach ($connectors as $connector) {
      $connection = $connector->getConnectionByUserId($user->id());
      $row = [
        $connector->label()
      ];
      if ($connection) {
        if ($user->id() == \Drupal::currentUser()->id()) {
          $row[] = [
            'data' => \Drupal::l($this->t('Unbind'), Url::fromRoute('dyniva_connect.user_unbind', [
              'user' => $user->id(),
              'connection' => $connection->id()
            ], [
              'query' => \Drupal::destination()->getAsArray()
            ]))
          ];
        }else{
          $row[] = $this->t('Connected');
        }
      }
      else {
        if ($user->id() != \Drupal::currentUser()->id()) {
          $row[] = $this->t('Not connected');
        }
        else {
          $query = \Drupal::destination()->getAsArray();
          $query['uid'] = $user->id();
          $query['timestamp'] = time();
          $query['token'] = user_pass_rehash($user, $query['timestamp']);
          $connect_url = Url::fromRoute('dyniva_connect.connect', [
            'connector' => $connector->id()
          ], [
            'query' => $query
          ])->setAbsolute();
          $image_data = "";
          if (\Drupal::moduleHandler()->moduleExists('dyniva_core')) {
            $image_data = \Drupal\dyniva_core\CcmsQrCode::fromText($connect_url->toString())->writeDataUri();
          }
          elseif (\Drupal::moduleHandler()->moduleExists('ccms_core')) {
            $image_data = \Drupal\ccms_core\CcmsQrCode::fromText($connect_url->toString())->writeDataUri();
          }
          if ($image_data) {
            $row[] = [
              'data' => new FormattableMarkup("<img src='@data' class='connection-image'/>", [
                '@data' => $image_data
              ])
            ];
          }
          else {
            $row[] = [
              'data' => \Drupal::l($this->t('connect'), Url::fromRoute('dyniva_connect.connect', [
                'connector' => $connector->id()
              ], [
                'query' => \Drupal::destination()->getAsArray()
              ]))
            ];
          }
        }
      }
      $rows[] = $row;
    }
    $build['table'] = [
      '#type' => 'table',
      '#header' => [
        $this->t('Connector Name'),
        $this->t('Status')
      ],
      '#rows' => $rows,
      '#empty' => $this->t('There are no connector yet.'),
      '#cache' => [
        'contexts' => [
          'user'
        ],
        'tags' => [
          'connector_list',
          'connection_list'
        ]
      ]
    ];
    return $build;
  }
  /**
   * User page.
   */
  public function userUnbind(User $user, Connection $connection) {
    if(\Drupal::currentUser()->id() == $connection->getOwnerId()){
      $connection->setOwnerId(0);
      $connection->save();
      $url = Url::fromRoute('dyniva_connect.user_page',['user' => $user->id()])->setAbsolute()->toString(TRUE)->getGeneratedUrl();
      $response = new TrustedRedirectResponse($url);
      return $response;
    }
    throw new AccessDeniedHttpException();
  }

}
