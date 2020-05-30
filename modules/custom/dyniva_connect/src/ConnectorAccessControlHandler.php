<?php

namespace Drupal\dyniva_connect;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Access controller for the Connector entity.
 *
 * @see \Drupal\dyniva_connect\Entity\Connector.
 */
class ConnectorAccessControlHandler extends EntityAccessControlHandler {
  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\dyniva_connect\ConnectorInterface $entity */
    switch ($operation) {
      case 'view':
        if (!$entity->isPublished()) {
          return AccessResult::allowedIfHasPermission($account, 'view unpublished connector entities');
        }
        return AccessResult::allowedIfHasPermission($account, 'view published connector entities');

      case 'update':
        return AccessResult::allowedIfHasPermission($account, 'edit connector entities');

      case 'delete':
        return AccessResult::allowedIfHasPermission($account, 'delete connector entities');
    }

    // Unknown operation, no opinion.
    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'add connector entities');
  }

}
