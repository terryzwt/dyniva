<?php

/**
 * @file
 * Message examples.
 */

use Drupal\Core\Entity\EntityInterface;
use Drupal\message\MessageInterface;
use Drupal\node\NodeInterface;
use Drupal\comment\CommentInterface;

/**
 * Implements hook_dyniva_message_get_subscribers().
 */
function hook_dyniva_message_get_subscribers(EntityInterface $entity, MessageInterface $message) {
  $uids = [];
  
  if($entity instanceof NodeInterface){
    $uids[$entity->getOwnerId()] = $entity->getOwnerId();
  }elseif ($entity instanceof CommentInterface) {
    $uids[$entity->getOwnerId()] = $entity->getOwnerId();
    $comment_entity = $entity->getCommentedEntity();
    $uids[$comment_entity->getOwnerId()] = $comment_entity->getOwnerId();
    if($entity->hasParentComment()){
      $parent = $entity->getParentComment();
      $uids[$parent->getOwnerId()] = $parent->getOwnerId();
    }
  }
  
  return $uids;
}
/**
 * Implements hook_dyniva_message_subscribers_alter().
 */
function hook_dyniva_message_subscribers_alter(&$uids, EntityInterface $entity, MessageInterface $message) {
  if($entity->getEntityTypeId() == 'node'){
    unset($uids[$entity->getOwnerId()]);
  }
}