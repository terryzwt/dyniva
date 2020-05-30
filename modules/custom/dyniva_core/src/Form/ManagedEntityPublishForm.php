<?php

namespace Drupal\dyniva_core\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\dyniva_core\Entity\ManagedEntity;
use Drupal\Core\Form\ConfirmFormBase;

/**
 * Builds the form to delete Managed entity entities.
 */
class ManagedEntityPublishForm extends ConfirmFormBase {
  /**
   * Managed entity.
   *
   * @var Drupal\dyniva_core\Entity\ManagedEntity
   */
  protected $managedEntity;

  /**
   * Entity.
   *
   * @var Drupal\Core\Entity\EntityInterface
   */
  protected $entity;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'managed_entity_publish_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $managed_entity = NULL, $managed_entity_id = NULL) {
    $this->managedEntity = $managed_entity;
    $this->entity = $managed_entity_id;

    $translation = \Drupal::entityManager()->getTranslationFromContext($this->entity);
    if ($translation->langcode->value != $this->entity->langcode->value) {
      $this->entity = $translation;
    }

    $form = parent::buildForm($form, $form_state);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return "";
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {

    $entity = $this->getDefaultRevision();
    $action = 'publish';
    if (!empty($entity->status->value)) {
      $action = 'unpublish';
    }
    $message = 'Are you sure you want to ' . $action . ' %name %label?';
    return $this->t($message, [
      '%name' => $this->managedEntity->label(),
      '%label' => $this->entity->label(),
    ]);
  }

  /**
   * Get default revision.
   */
  public function getDefaultRevision() {
    /*
     * @var \Drupal\content_moderation\ModerationInformation $moderation_info
     */
    $moderation_info = \Drupal::service('content_moderation.moderation_information');
    $default_revision = $moderation_info->getDefaultRevisionId($this->entity->getEntityTypeId(), $this->entity->id());
    if ($this->entity->getRevisionId() != $default_revision) {
      return \Drupal::entityTypeManager()->getStorage($this->entity->getEntityTypeId())->loadRevision($default_revision);
    }
    return $this->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    if ($dest = \Drupal::destination()->get()) {
      return Url::fromUserInput($dest);
    }
    return Url::fromUserInput('manage/' . $this->managedEntity->id());
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    $entity = $this->getDefaultRevision();

    $action = t('Published');
    if(!empty($entity->status->value)){
      $action = t('Unpublished');
    }
    return $action;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $entity = $this->getDefaultRevision();
    $action = $this->getConfirmText();

    if (!empty($entity->status->value)) {
      $this->entity->moderation_state->value = 'unpublished';
    }
    else {
      $this->entity->moderation_state->value = 'published';
    }

    $this->entity->save();

    $message = 'Content @type @label has ' . $action . '.';
    drupal_set_message($this->t($message, [
      '@type' => $this->managedEntity->label(),
      '@label' => $this->entity->label(),
    ]));

    $form_state->setRedirectUrl($this->getCancelUrl());
  }

}
