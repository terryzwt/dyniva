<?php

namespace Drupal\dyniva_private_message\Controller;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\private_message\Service\PrivateMessageServiceInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Controller to handle Page.
 */
class PageController extends ControllerBase {

  const AUTOCOMPLETE_COUNT = 10;

  /**
   * The renderer service.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * The entity manager service.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * The configuration factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * The private message service.
   *
   * @var \Drupal\private_message\Service\PrivateMessageServiceInterface
   */
  protected $privateMessageService;

  /**
   * Constructs n AjaxController object.
   *
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer service.
   * @param \Symfony\Component\HttpFoundation\RequestStack $requestStack
   *   The request stack.
   * @param \Drupal\Core\Entity\EntityManagerInterface $entityManager
   *   The entity manager service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The configuration factory.
   * @param \Drupal\Core\Session\AccountProxyInterface $currentUser
   *   The current user.
   * @param \Drupal\private_message\Service\PrivateMessageServiceInterface $privateMessageService
   *   The private message service.
   */
  public function __construct(RendererInterface $renderer, RequestStack $requestStack, EntityManagerInterface $entityManager, ConfigFactoryInterface $configFactory, AccountProxyInterface $currentUser, PrivateMessageServiceInterface $privateMessageService) {
    $this->renderer = $renderer;
    $this->requestStack = $requestStack;
    $this->entityManager = $entityManager;
    $this->configFactory = $configFactory;
    $this->currentUser = $currentUser;
    $this->privateMessageService = $privateMessageService;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('renderer'),
      $container->get('request_stack'),
      $container->get('entity.manager'),
      $container->get('config.factory'),
      $container->get('current_user'),
      $container->get('private_message.service')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function adminListPage() {
    $this->privateMessageService->updateLastCheckTime();

    $user = $this->entityManager->getStorage('user')->load($this->currentUser->id());
    $private_message_thread = $this->privateMessageService->getFirstThreadForUser($user);

    if($private_message_thread instanceof \Drupal\Core\Entity\EntityInterface) {
      return new RedirectResponse(\Drupal::url('dyniva_message.manage.private_message_thread.canonical', [
        'private_message_thread' => $private_message_thread->id()
      ]));
    }
    
    $layoutPluginManager = \Drupal::service('plugin.manager.core.layout');
    $layoutInstance = $layoutPluginManager->createInstance('dyniva_admin_layout_2col_4_8', []);

    // Build the content for your regions.
    $regions = [
      'top' => $this->topBuild('Private Messages'),
      'left' => [
        'view' => [
          '#markup' => '<p class="empty">'.$this->t('No message.').'</p><br/><a href="#" data-action="goback">'.$this->t('Go Back').'</a>'
        ],
      ],
    ];
    

    // This builds the render array.
    $page = $layoutInstance->build($regions);
    $page['#attached']['library'][] = 'dyniva_private_message/jquery_goback';
    return $page;
  }

  public function adminViewPage($private_message_thread) {
    $user = $this->entityManager->getStorage('user')->load($this->currentUser->id());
    $layoutPluginManager = \Drupal::service('plugin.manager.core.layout');
    $layoutInstance = $layoutPluginManager->createInstance('dyniva_admin_layout_2col_4_8', []);
    $thread = $this->entityManager->getStorage('private_message_thread')->load($private_message_thread);
    $view = [];
    if($thread instanceof \Drupal\Core\Entity\EntityInterface) {
      // save不要触发缓存清空
      $thread->updateLastAccessTime($user)->save(false);
      $view_mode = 'default';
      $view_builder = $this->entityManager->getViewBuilder($thread->getEntityTypeId());
      $view = $view_builder->view($thread, $view_mode);
    }

    // Build the content for your regions.
    $regions = [
      'top' => $this->topBuild('Private Message'),
      'left' => $this->listBuild(),
      'content' => [
        'view' => $view,
      ],
    ];

    // This builds the render array.
    return $layoutInstance->build($regions);
  }

  public function accessAdminViewPage(AccountInterface $account, $private_message_thread) {
    $thread = $this->entityManager->getStorage('private_message_thread')->load($private_message_thread);
    return $thread->access('view', null, true);
  }

  public function adminNewPage() {
    $layoutPluginManager = \Drupal::service('plugin.manager.core.layout');
    $layoutInstance = $layoutPluginManager->createInstance('dyniva_admin_layout_2col_4_8', []);

    $entity = $this->entityManager->getStorage('private_message')->create();
    $form = \Drupal::service('entity.form_builder')->getForm($entity, 'add');
    if(isset($form['message']['widget'][0]['format']['format']))
      $form['message']['widget'][0]['format']['format']['#access'] = false;

    // Build the content for your regions.
    $regions = [
      'left' => $this->listBuild(),
      'content' => [
        'new' => $form
      ],
    ];

    // This builds the render array.
    return $layoutInstance->build($regions);
  }
  
  /**
   * {@inheritdoc}
   */
  private function topBuild($title) {
    \Drupal::entityTypeManager()->getAccessControlHandler('private_message')->createAccess();
    return [
      '#theme' => 'dyniva_private_message_manage_top_block',
      '#title' => $this->t($title),
      '#can_create' => \Drupal::entityTypeManager()->getAccessControlHandler('private_message')->createAccess()
    ];
  }

  /**
   * {@inheritdoc}
   */
  private function listBuild() {
    $plugin = 'views_block:dyniva_private_message-block_list';
    $list = \Drupal::service('plugin.manager.block')
      ->createInstance($plugin, [])
      ->build();
    return [
      '#theme' => 'block',
      '#plugin_id' => $plugin,
      '#base_plugin_id' => $plugin,
      '#derivative_plugin_id' => $plugin,
      '#configuration' => [
        "label" => "",
        "provider" => "dyniva_message",
        "label_display" => 1
      ],
      'content' => $list
    ];
  }

}
