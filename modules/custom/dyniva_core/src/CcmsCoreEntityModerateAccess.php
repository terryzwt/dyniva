<?php

namespace Drupal\dyniva_core;

use Drupal\dyniva_core\Entity\ManagedEntity;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\taxonomy\Entity\Term;

/**
 * TODO Missing class doc comment.
 */
class CcmsCoreEntityModerateAccess {

  /**
   * TODO Missing function doc comment.
   */
  public function entityAccess(ManagedEntity $managed_entity, EntityInterface $managed_entity_id, $op = 'edit') {

    $account = \Drupal::currentUser();

    if (in_array('administrator', $account->getRoles())) {
      return AccessResult::allowed();
    }
    $author_id = $account->id();
    if (!empty($managed_entity_id->uid->target_id)) {
      $author_id = $managed_entity_id->uid->target_id;
    }
    $moderation_allow = TRUE;

    if (in_array('webmaster', $account->getRoles())) {
      $status_array = ['draft', 'unpublished', 'published', 'need_approve'];
    }
    else {
      $status_array = ['draft', 'unpublished', 'published'];
    }

    if (\Drupal::service('content_moderation.moderation_information')->isModeratedEntity($managed_entity_id)) {
      if (!empty($managed_entity_id->moderation_state->value) &&
        !in_array($managed_entity_id->moderation_state->value, $status_array)) {
        $moderation_allow = FALSE;
      }
    }

    $access = AccessResult::forbidden();
    if (!$moderation_allow && !$account->hasPermission('manage ccms content moderation')) {
      $access = AccessResult::forbidden();
    }elseif (($account->hasPermission("{$op} own ccms {$managed_entity->id()}") && $author_id == $account->id())
      || $account->hasPermission("{$op} any ccms {$managed_entity->id()}")) {
      if (\Drupal::moduleHandler()->moduleExists('dyniva_permission')) {
        if ($this->ccmsPermissionAccess($managed_entity_id, $op, $account)) {
          $access = AccessResult::allowed();
        }
        else {
          $access = AccessResult::forbidden();
        }
      }
      else {
        $access = AccessResult::allowed();
      }
    }

    $result = \Drupal::moduleHandler()->invokeAll('ccms_entity_moderate_access', [$managed_entity, $managed_entity_id, $op, $account]);
    foreach ($result as $value) {
      if ($value instanceof AccessResult) $access = $access->andIf($value);
    }
    return $access;
  }

  /**
   * TODO Missing function doc comment.
   */
  public function permissionAccess(ManagedEntity $managed_entity, $permission) {
    if (\Drupal::currentUser()->hasPermission($permission)) {
      return AccessResult::allowed();
    }
    return AccessResult::forbidden();
  }

  /**
   * TODO Missing function doc comment.
   */
  public function ccmsPermissionAccess(EntityInterface $entity, $operation, $account) {

    if ($account->hasPermission('by pass ccms premission')) {
      return TRUE;
    }

    if ($operation == 'update' || $operation == 'edit'  || $operation == 'delete') {
      $field_name = \Drupal::state()->get('ccms_permission.permission_field_name', 'department');
      if ($entity instanceof FieldableEntityInterface && $entity->hasField($field_name)) {
        if (empty($entity->{$field_name}->target_id)) {
          return TRUE;
        }
        else {
          $tid = $entity->{$field_name}->target_id;
          if (dyniva_permission_user_has_permisson($tid, $account->id())) {
            return TRUE;
          }
          else {
            return FALSE;
          }
        };

      }
      if ($entity instanceof Term) {
        if ($entity->getVocabularyId() == $field_name) {
          if (dyniva_permission_user_has_permisson($entity->id(), $account->id())) {
            return TRUE;
          }
          else {
            return FALSE;
          }
        }
      }
    }

    return TRUE;
  }

}
