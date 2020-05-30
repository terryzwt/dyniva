<?php

namespace Drupal\dyniva_core\Form;

use Drupal\content_translation\Form\ContentTranslationDeleteForm as ContentTranslationDeleteFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Delete translation form for content_translation module.
 *
 * @internal
 */
class ContentTranslationDeleteForm extends ContentTranslationDeleteFormBase {

  /**
   * {@inheritdoc}
   */
  protected function init(FormStateInterface $form_state) {
    $this->prepareEntity();
    $this->moduleHandler = \Drupal::moduleHandler();
    parent::init($form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function getBaseFormId() {
    return '';
  }

  /**
   * {@inheritdoc}
   *
   * @see \Drupal\Core\Entity\ContentEntityForm::prepareEntity()
   */
  protected function prepareEntity() {
    $entity = \Drupal::routeMatch()->getParameter('managed_entity_id');
    $language = \Drupal::routeMatch()->getParameter('language');
    if ($language) {
      $entity = $entity->getTranslation($language->getId());
    }
    $this->setEntity($entity);

    parent::prepareEntity();
  }

}
