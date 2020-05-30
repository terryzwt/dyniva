<?php

namespace Drupal\dyniva_editor_panelizer\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\FieldableEntityInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Drupal\Core\Link;
use Drupal\user\Entity\User;
use Drupal\Core\Language\LanguageInterface;

/**
 * Controller for Picl's Panels IPE routes.
 */
class PiclPanelsIPEController extends ControllerBase {

  /**
   * Reverts an entity view mode to a particular named default.
   *
   * @param \Drupal\Core\Entity\FieldableEntityInterface $entity
   *   The entity.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   An empty response.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\BadRequestHttpException
   */
  public function locked(FieldableEntityInterface $entity) {
    $user = $this->currentUser();
    $lock_service = \Drupal::service('content_lock');

    if ($lock = $lock_service->fetchLock($entity->id(), $entity->getEntityTypeId())) {

      if ($lock->uid == $user->id()) {
        $lock->self = TRUE;
      }
      $lock->info = $this->getLockInfo($lock);
    }

    return new JsonResponse($lock);
  }

  /**
   * {@inheritdoc}
   */
  public function locking(FieldableEntityInterface $entity) {
    $user = $this->currentUser();
    $lock_service = \Drupal::service('content_lock');
    $lock_service->locking($entity->id(), $user->id(), $entity->getEntityTypeId(), TRUE);
    if ($lock = $lock_service->fetchLock($entity->id(), $entity->getEntityTypeId())) {
      if ($lock->uid == $user->id()) {
        $lock->self = TRUE;
      }
      $lock->info = $this->getLockInfo($lock);
    }

    return new JsonResponse($lock);
  }

  /**
   * {@inheritdoc}
   */
  public function unlock(FieldableEntityInterface $entity) {
    $user = $this->currentUser();
    $lock_service = \Drupal::service('content_lock');
    $lock_service->release($entity->id(), $user->id(), $entity->getEntityTypeId());

    return new JsonResponse();
  }

  /**
   * {@inheritdoc}
   */
  protected function getLockInfo($lock) {
    $info = t('The content is locked by <a href="/user/@uid" target="_blank" class="user">@name</a> for', [
      '@uid' => User::load($lock->uid)->id(),
      '@name' => User::load($lock->uid)->getDisplayName(),
    ]);
    $info .= t('@interval.', [
      '@interval' => \Drupal::service('date.formatter')->formatInterval(REQUEST_TIME - $lock->timestamp, 1),
    ]);

    if (\Drupal::service('module_handler')->moduleExists('content_lock_timeout')) {
      $config = \Drupal::config('content_lock_timeout.settings');
      $timeout_minutes = $config->get('content_lock_timeout_minutes');
      $info .= '<br>' . t('The lock will be released in @interval.', [
        '@interval' => \Drupal::service('date.formatter')->formatInterval($lock->timestamp + 60 * $timeout_minutes - REQUEST_TIME, 1),
      ]);
    }

    if (\Drupal::currentUser()->hasPermission('break content lock')) {
      $link_options = [
        'attributes' => [
          'class' => [
            'break-lock',
          ],
        ],
      ];
      $link = Link::createFromRoute(
        t('Break lock now'),
        'content_lock.break_lock.' . $lock->entity_type,
        [
          'entity' => $lock->entity_id,
          'langcode' => LanguageInterface::LANGCODE_NOT_SPECIFIED,
          'form_op' => 'edit',
        ],
        $link_options
      );
      $info .= '<br/>' . $link->toString();
    }

    $return = '<ul class="ipe-tabs">';
    $return .= '<li class="ipe-tab">';
    $return .= '<p style="padding: 10px;">';
    $return .= $info;
    $return .= '</p>';
    $return .= '</li>';
    $return .= '</ul>';

    return $return;
  }

}
