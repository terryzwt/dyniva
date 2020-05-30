<?php

namespace Drupal\dyniva_media\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\lightning_media\Form\MediaForm as BaseMediaForm;
use Drupal\Core\Url;

/**
 * Adds dynamic preview support to the media entity form.
 */
class MediaForm extends BaseMediaForm {

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    parent::save($form, $form_state);

    $query = $this->getRequest()->query;

    if ($query->has('bulk-create')) {
      /** @var \Drupal\Core\Entity\EntityInterface $entity */
      $entity = $this->getEntity();

      if(\Drupal::moduleHandler()->moduleExists('ccms_core')){
        $managed_entity = ccms_core_get_entity_managed_entity($entity);
      }else{
        $managed_entity = dyniva_core_get_entity_managed_entity($entity);
      }

      // If there are more entities to create, redirect to the edit form for the
      // next one in line.
      $queue = $query->get('bulk-create', []);
      if (is_array($queue)) {
        $id = array_shift($queue);
        if ($managed_entity) {
          if(\Drupal::moduleHandler()->moduleExists('ccms_core')){
            $redirect = Url::fromRoute("ccms_core.managed_entity.{$managed_entity->id()}.edit_page", ['managed_entity_id' => $id], [
              'query' => [
                'bulk-create' => $queue ?: TRUE,
              ],
            ]);
          }else{
            $redirect = Url::fromRoute("dyniva_core.managed_entity.{$managed_entity->id()}.edit_page", ['managed_entity_id' => $id], [
              'query' => [
                'bulk-create' => $queue ?: TRUE,
              ],
            ]);
          }
          $form_state->setRedirectUrl($redirect);
        }
      }
      // Otherwise, try to redirect to the entity type's collection.
      else {
        if ($managed_entity) {
          try {
            $redirect = Url::fromRoute("view.manage_{$managed_entity->id()}.page_list");
            $form_state->setRedirectUrl($redirect);
          }
          catch (\Exception $e) {
          }
        }
      }
    }
  }

}
