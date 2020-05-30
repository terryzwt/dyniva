<?php

namespace Drupal\dyniva_connect;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Access controller for the Connection entity.
 *
 * @see \Drupal\dyniva_connect\Entity\Connection.
 */
class ConnectionAccessControlHandler extends EntityAccessControlHandler {
  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\dyniva_connect\ConnectionInterface $entity */
    switch ($operation) {
      case 'view':
        if (!$entity->isPublished()) {
          return AccessResult::allowedIfHasPermission($account, 'view unpublished connection entities');
        }
        return AccessResult::allowedIfHasPermission($account, 'view published connection entities');

      case 'update':
        return AccessResult::allowedIfHasPermission($account, 'edit connection entities');

      case 'delete':
        return AccessResult::allowedIfHasPermission($account, 'delete connection entities');
    }

    // Unknown operation, no opinion.
    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'add connection entities');
  }

}
