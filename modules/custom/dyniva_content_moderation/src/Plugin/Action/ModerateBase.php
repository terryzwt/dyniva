<?php

namespace Drupal\dyniva_content_moderation\Plugin\Action;

use Drupal\Core\Action\ActionBase;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\Entity;
use Drupal\Component\Plugin\PluginBase;

/**
 * Moderate base.
 */
class ModerateBase extends ActionBase {
  
  /**
   * @var string
   */
  protected $source_state;
  
  /**
   * @var string
   */
  protected $target_state;
  
  public function __construct(array $configuration, $plugin_id, $plugin_definition, $source_state = '', $target_state = '') {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->source_state = $source_state;
    $this->target_state = $target_state;
  }

  /**
   * {@inheritdoc}
   */
  public function execute(Entity $entity = NULL) {
    if(isset($entity->moderation_state->value) && $entity->moderation_state->value == $this->source_state){
      $entity->moderation_state->value = $this->target_state;
      $entity->save();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function access($entity, AccountInterface $account = NULL, $return_as_object = FALSE) {
    if(isset($entity->moderation_state->value) && $entity->moderation_state->value == $this->source_state){
      return $return_as_object?AccessResult::allowed():TRUE;
    }
    return $return_as_object?AccessResult::forbidden():FALSE;
  }
  /**
   * Moderation access check
   * @param AccountInterface $account
   * @return boolean|\Drupal\Core\Access\AccessResult
   */
  public function actionAccess(AccountInterface $account = NULL) {
    if($account == NULL){
      $account = \Drupal::currentUser();
    }
    $storage = \Drupal::entityTypeManager()->getStorage('moderation_state');
    $from = $storage->load($this->source_state);
    $to = $storage->load($this->target_state);
    if($from && $to){
      $transition_validation = \Drupal::service('content_moderation.state_transition_validation');
      return $transition_validation->userMayTransition($from, $to, $account);
    }
    return false;
  }

}
