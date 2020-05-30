<?php

namespace Drupal\dyniva_content_moderation\Entity\Handler;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\content_moderation\Entity\Handler\ModerationHandler;
use Drupal\ccms_deploy\Entity\DeploymentEntity;

/**
 * Customizations for node entities.
 */
class CustomModerationHandler extends ModerationHandler {

  /**
   * {@inheritdoc}
   */
  public function onPresave(ContentEntityInterface $entity, $default_revision, $published_state) {
    if($entity instanceof DeploymentEntity){
      $entity->set('status',$published_state);
      return;
    }
    
    if ($this->shouldModerate($entity, $published_state)) {
//       parent::onPresave($entity, $default_revision, $published_state);
      $entity->set('status',$published_state);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function enforceRevisionsEntityFormAlter(array &$form, FormStateInterface $form_state, $form_id) {
  }

  /**
   * {@inheritdoc}
   */
  public function enforceRevisionsBundleFormAlter(array &$form, FormStateInterface $form_state, $form_id) {
  }

  /**
   * Check if an entity's default revision and/or state needs adjusting.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity to check.
   * @param bool $published_state
   *   Whether the state being transitioned to is a published state or not.
   *
   * @return bool
   *   TRUE when either the default revision or the state needs to be updated.
   */
  protected function shouldModerate(ContentEntityInterface $entity, $published_state) {
    // @todo clarify the first condition.
    // First condition is needed so you can add a translation.
    // Second condition checks to see if the published status has changed.
    return $entity->get('status') !== $published_state;
  }
}
