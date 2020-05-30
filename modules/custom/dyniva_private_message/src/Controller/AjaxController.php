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

/**
 * Controller to handle Ajax requests.
 */
class AjaxController extends ControllerBase {

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
      $container->get('dyniva_private_message.service')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function ajaxCallback($op) {
    $response = new AjaxResponse();

    if ($this->currentUser->hasPermission('use private messaging system')) {
      switch ($op) {

        case 'update_last_check_time':
          $this->updateLastCheckTime($response);
          break;
      }
    }

    return $response;
  }

  public function unreadThreads() {
    $dateFormatter = \Drupal::service('date.formatter');
    $ids = $this->privateMessageService->getUnreadThreadIds();
    $threads = \Drupal::service('entity.manager')->getStorage('private_message_thread')->loadMultiple($ids);
    foreach($threads as $thread) {
      $messages = $thread->getMessages();
      $message = array_pop($messages);
      $text = strip_tags($message->getMessage());
      $author = $message->getOwner();
      $picture = '';
      if($author->hasField('user_picture') && !$author->user_picture->isEmpty()) {
        $picture = ImageStyle::load('small')->buildUrl($author->user_picture->entity->getFileUri());
      } elseif(\Drupal::moduleHandler()->moduleExists('dyniva_core')) {
        $picture = dyniva_core_get_user_picture($author);
        $picture = $picture['picture'];
      }
      $results []= [
        'message' => Mbstring::mb_strlen($text) <= 10 ? $text : Mbstring::mb_substr($text, 0, 10).'...',
        'date' => $dateFormatter->format($message->getCreatedTime(), 'custom', 'Y-m-d\TH:i:s\Z'),
        'timestamp' => $message->getCreatedTime(),
        'owner' => $author->getDisplayName(),
        'image' => $picture
      ];
    }
  }

  /**
   * Load a private message thread to be dynamically inserted into the page.
   *
   * @param Drupal\Core\Ajax\AjaxResponse $response
   *   The response to which any commands should be attached.
   */
  protected function updateLastCheckTime(AjaxResponse $response) {
    $this->privateMessageService->updateLastCheckTime();
  }

}
