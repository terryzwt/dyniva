<?php

namespace Drupal\dyniva_private_message\Plugin\EntityReferenceSelection;

use Drupal\Core\Entity\Plugin\EntityReferenceSelection\DefaultSelection;

/**
 * Provides specific access control for the user entity type.
 *
 * @EntityReferenceSelection(
 *   id = "dyniva_private_message:user",
 *   label = @Translation("User selection of dyniva"),
 *   entity_types = {"user"},
 *   group = "default",
 *   weight = 1
 * )
 */
class UserSelection extends DefaultSelection {

  
  /**
   * {@inheritdoc}
   */
  protected function buildEntityQuery($match = NULL, $match_operator = 'CONTAINS') {
    $query = parent::buildEntityQuery($match, $match_operator);

    $query->condition('uid', 0, '<>');
    $query->condition('uid', \Drupal::currentUser()->id(), '<>');

    $user = \Drupal::service('entity.manager')->getStorage('user')->load(\Drupal::currentUser()->id());

    // The user entity doesn't have a label column.
    if (isset($match)) {
      if(isset($user->full_name)) {
        $and_condition = $query->orConditionGroup()
          ->condition('name', $match, $match_operator)
          ->condition('full_name', $match, $match_operator);
        $query->condition($and_condition);
      } else {
        $query->condition('name', $match, $match_operator);
      }
    }

    $query->condition('status', 1);
    return $query;
  }

}
