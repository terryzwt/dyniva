<?php

namespace Drupal\dyniva_message\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Drupal\views\Views;

/**
 * Provides the controller for Notification entity pages.
 */
class NotificationController extends ControllerBase {

  public function list() {
    // Output page
    $view = Views::getView('dyniva_notifications');
    $view->setDisplay('block_1');
    $view->preExecute();
    $view->execute();
    return $view->render();
  }

  /**
   * Ajax callback. Returns unread notifications count.
   */
  public function count() {
    $storage = $this->entityManager()->getStorage('flagging');
    $ids = $storage->getQuery()
      ->condition('uid', \Drupal::currentUser()->id())
      ->condition('flag_id', 'subscribe_message')
      ->condition('read', false)
      ->execute();
    return new JsonResponse([
      'count' => count($ids)
    ]);
  }

  /**
   * Ajax callback. Marks the notification as read.
   */
  public function markRead($id) {
    if ($id === NULL) {
      throw new NotFoundHttpException();
    }

    $flag = $this->entityManager()->getStorage('flagging')->load($id);
    $flag->set('read', true);
    $flag->save();
    return new JsonResponse([]);
  }

  public function makeAllRead() {
    // Read All
    $storage = $this->entityManager()->getStorage('flagging');

    $ids = $storage->getQuery()
      ->condition('uid', \Drupal::currentUser()->id())
      ->condition('flag_id', 'subscribe_message')
      ->condition('read', false)
      ->execute();

    $flags = $storage->loadMultiple($ids);

    foreach ($flags as $flag) {
      $flag->set('read', true);
      $flag->save();
    }
    return new JsonResponse([]);
  }

}
