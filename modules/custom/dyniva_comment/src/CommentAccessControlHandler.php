<?php

namespace Drupal\dyniva_comment;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\comment\CommentAccessControlHandler as SuperCommentAccessControlHandler;


class CommentAccessControlHandler extends SuperCommentAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    $account = \Drupal::currentUser();
    if($account->hasPermission('delete own comments') && $operation == 'delete' && $entity->getOwnerId() == $account->id()) {
      return AccessResult::allowed();
    }
    return parent::checkAccess($entity, $operation, $account);
  }

}
