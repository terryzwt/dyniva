<?php

namespace Drupal\dyniva_core\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Url;
use Drupal\Core\Messenger\MessengerTrait;
use Drupal\Core\Entity\EntityInterface;
use Drupal\dyniva_core\EntityCloneHelper;

/**
 * Implements an entity Clone form.
 */
class EntityCloneConfirmForm extends ConfirmFormBase {
  use MessengerTrait;
  
  /**
   * The entity to be clone.
   * @var EntityInterface
   */
  protected $entity;
  
  /**
   * The managedEntity.
   * @var EntityInterface
   */
  protected $managedEntity;
  
  
  /**
   *
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'entity_clone_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, EntityInterface $managedEntity = NULL, EntityInterface $entity = NULL) {
    $this->managedEntity = $managedEntity;
    $this->entity = $entity;
    
    return parent::buildForm($form, $form_state);
  }
  /**
   *
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $cloned_entity = EntityCloneHelper::cloneEntity($this->entity);
    EntityCloneHelper::setClonedEntityLabel($this->entity, $cloned_entity);
    $cloned_entity->save();
    $this->messenger()->addMessage($this->t('Clone success.'));
  
  }

  /**
   * Gets the entity of this form.
   *
   * @return \Drupal\Core\Entity\EntityInterface The entity.
   */
  public function getEntity() {
    return $this->entity;
  }
  /**
   *
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t("Are you sure to clone @label?",['@label' => $this->entity->label()]);
  }
  /**
   *
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    if($dest = \Drupal::destination()->get()){
      return Url::fromUserInput($dest);
    }
    return Url::fromUserInput('manage/' . $this->managedEntity->id());
  }

}
