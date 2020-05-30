<?php

namespace Drupal\dyniva_private_message\Plugin\Block;

use Drupal\Core\Access\CsrfTokenGenerator;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Block\BlockPluginInterface;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Url;
use Drupal\private_message\Service\PrivateMessageServiceInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Datetime\Entity\DateFormat;
use Symfony\Polyfill\Mbstring\Mbstring;
use Drupal\image\Entity\ImageStyle;

/**
 * Provides the private message notification block.
 *
 * @Block(
 *   id = "dyniva_private_message_notification_block",
 *   admin_label = @Translation("Private Message Notification Toolbar"),
 *   category =  @Translation("Dyniva Private Message"),
 * )
 */
class PrivateMessageNotificationBlock extends BlockBase implements BlockPluginInterface, ContainerFactoryPluginInterface {

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * The CSRF token generator service.
   *
   * @var \Drupal\Core\Access\CsrfTokenGenerator
   */
  protected $csrfToken;

  /**
   * The private message service.
   *
   * @var \Drupal\private_message\Service\PrivateMessageServiceInterface
   */
  protected $privateMessageService;

  /**
   * Constructs a PrivateMessageForm object.
   *
   * @param array $configuration
   *   The block configuration.
   * @param string $plugin_id
   *   The ID of the plugin.
   * @param mixed $plugin_definition
   *   The plugin definition.
   * @param \Drupal\Core\Session\AccountProxyInterface $currentUser
   *   The current user.
   * @param \Drupal\Core\Access\CsrfTokenGenerator $csrfToken
   *   The CSRF token generator service.
   * @param \Drupal\private_message\Service\PrivateMessageServiceInterface $privateMessageService
   *   The private message service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, AccountProxyInterface $currentUser, CsrfTokenGenerator $csrfToken, PrivateMessageServiceInterface $privateMessageService) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->currentUser = $currentUser;
    $this->csrfToken = $csrfToken;
    $this->privateMessageService = $privateMessageService;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('current_user'),
      $container->get('csrf_token'),
      $container->get('dyniva_private_message.service')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    if ($this->currentUser->isAuthenticated()) {
      $block = [
        '#theme' => 'dyniva_private_message_notification_block',
        '#new_message_count' => $this->privateMessageService->getUnreadThreadCount(),
        '#first_thread' => $this->privateMessageService->getFirstThreadForUser(user_load($this->currentUser->id())),
        '#has_create_private_message' => $this->currentUser->hasPermission('create private message')
      ];

      $url = Url::fromRoute('dyniva_private_message.ajax_callback', ['op' => 'update_last_check_time']);
      $token = $this->csrfToken->get($url->getInternalPath());
      $url->setOptions(['absolute' => TRUE, 'query' => ['token' => $token]]);
      $block['#attached']['drupalSettings']['dynivaPrivateMessageNotificationBlock']['loadThreadCallback'] = $url->toString();

      // $config = $this->getConfiguration();
      // $block['#attached']['drupalSettings']['privateMessageNotificationBlock']['ajaxRefreshRate'] = $config['ajax_refresh_rate'];

      $block['#attached']['library'][] = 'dyniva_private_message/notification_block';
      $block['#attached']['library'][] = 'dyniva_private_message/jquery_showif';

      if($block['#new_message_count'] > 0) {
        $results = [];
        $ids = $this->privateMessageService->getUnreadThreadIds();
        $ids = array_reverse($ids);
        if(count($ids) > 5) {
          $ids = array_slice($ids, 0, 5);
        }
        $threads = \Drupal::service('entity.manager')->getStorage('private_message_thread')->loadMultiple($ids);
        foreach($threads as $thread) {
          $messages = $thread->getMessages();
          $message = array_pop($messages);
          $text = strip_tags($message->getMessage());
          $search = array(" ","　","\n","\r","\t",'&nbsp;');
          $replace = array("","","","","","");
          $text = str_replace($search, $replace, $text);
          $author = $message->getOwner();
          $picture = '';
          if(\Drupal::moduleHandler()->moduleExists('dyniva_core')) {
            if($author) {
              $picture = dyniva_core_get_user_picture($author);
              $picture = $picture['picture'];
            } else {
              $picture = file_create_url(drupal_get_path('module', 'dyniva_core') . '/img/user_default.png');
            }
          } elseif(\Drupal::moduleHandler()->moduleExists('ccms_core')) {
            if($author) {
              $picture = ccms_core_get_user_picture($author);
              $picture = $picture['picture'];
            } else {
              $picture = file_create_url(drupal_get_path('module', 'ccms_core') . '/img/user_default.png');
            }
          }
          //dyniva_message.manage.private_message_thread.canonical
          $link = Url::fromRoute('dyniva_message.manage.private_message_thread.canonical', ['private_message_thread' => $thread->id()])->toString();
          $results []= [
            'message' => Mbstring::mb_strlen($text) <= 10 ? $text : Mbstring::mb_substr($text, 0, 10).'...',
            'date' => date('c', $message->getCreatedTime()),
            'timestamp' => $message->getCreatedTime(),
            'owner' => $author->getDisplayName(),
            'image' => $picture,
            'link' => $link
          ];
        }
        $block['#items'] = $results;
      }
      $block['#you_has_new_text'] = $this->t('You have @new_message_count message', ['@new_message_count' => $block['#new_message_count']]);

      // Add the default classes, as these are not added when the block output
      // is overridden with a template.
      $block['#attributes']['class'][] = 'block';
      $block['#attributes']['class'][] = 'block-private-message-toolbar';
      $block['#cache']['max-age'] = 0;
      $block['#cache']['contexts'] = [];
      $block['#cache']['tags'] = [];

      return $block;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    return Cache::mergeTags(parent::getCacheTags(), ['dyniva_private_message_notification_block:uid:' . $this->currentUser->id()]);
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    // Vary caching of this block per user.
    return Cache::mergeContexts(parent::getCacheContexts(), ['user']);
  }

}
