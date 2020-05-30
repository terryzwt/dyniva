<?php

namespace Drupal\dyniva_core\Controller;

use Drupal\content_translation\ContentTranslationManager;
use Drupal\content_translation\ContentTranslationManagerInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Language\LanguageInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityInterface;

/**
 * Base class for entity translation controllers.
 */
class ContentTranslationController extends ControllerBase {

  /**
   * The content translation manager.
   *
   * @var \Drupal\content_translation\ContentTranslationManagerInterface
   */
  protected $manager;

  /**
   * Initializes a content translation controller.
   *
   * @param \Drupal\content_translation\ContentTranslationManagerInterface $manager
   *   A content translation manager instance.
   */
  public function __construct(ContentTranslationManagerInterface $manager) {
    $this->manager = $manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('content_translation.manager'));
  }

  /**
   * Populates target values with the source values.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity being translated.
   * @param \Drupal\Core\Language\LanguageInterface $source
   *   The language to be used as source.
   * @param \Drupal\Core\Language\LanguageInterface $target
   *   The language to be used as target.
   */
  public function prepareTranslation(ContentEntityInterface $entity, LanguageInterface $source, LanguageInterface $target) {
    /* @var \Drupal\Core\Entity\ContentEntityInterface $source_translation */
    $source_translation = $entity->getTranslation($source->getId());
    $target_translation = $entity->addTranslation($target->getId(), $source_translation->toArray());

    // Make sure we do not inherit the affected status from the source values.
    if ($entity->getEntityType()->isRevisionable()) {
      $target_translation->setRevisionTranslationAffected(NULL);
    }

    /** @var \Drupal\user\UserInterface $user */
    $user = $this->entityManager()->getStorage('user')->load($this->currentUser()->id());
    $metadata = $this->manager->getTranslationMetadata($target_translation);

    // Update the translation author to current user, as well the translation
    // creation time.
    $metadata->setAuthor($user);
    $metadata->setCreatedTime(REQUEST_TIME);
  }

  /**
   * Builds an add translation page.
   *
   * @param \Drupal\Core\Language\LanguageInterface $source
   *   The language of the values being translated. Defaults to the entity
   *   language.
   * @param \Drupal\Core\Language\LanguageInterface $target
   *   The language of the translated values. Defaults to the current content
   *   language.
   * @param \Drupal\Core\Entity\EntityInterface $managed_entity
   *   The managed entity.
   * @param \Drupal\Core\Entity\EntityInterface $managed_entity_id
   *   The managed entity id.
   *
   * @return array
   *   A processed form array ready to be rendered.
   */
  public function add(LanguageInterface $source, LanguageInterface $target, EntityInterface $managed_entity, EntityInterface $managed_entity_id) {
    /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
    $entity = $managed_entity_id;

    // In case of a pending revision, make sure we load the latest
    // translation-affecting revision for the source language, otherwise the
    // initial form values may not be up-to-date.
    if (!$entity->isDefaultRevision() && ContentTranslationManager::isPendingRevisionSupportEnabled($entity->getEntityTypeId(), $entity->bundle())) {
      /** @var \Drupal\Core\Entity\ContentEntityStorageInterface $storage */
      $storage = $this->entityTypeManager()->getStorage($entity->getEntityTypeId());
      $revision_id = $storage->getLatestTranslationAffectedRevisionId($entity->id(), $source->getId());
      if ($revision_id != $entity->getRevisionId()) {
        $entity = $storage->loadRevision($revision_id);
      }
    }

    // @todo Exploit the upcoming hook_entity_prepare() when available.
    // See https://www.drupal.org/node/1810394.
    $this->prepareTranslation($entity, $source, $target);

    $operation = $entity->getEntityType()->hasHandlerClass('form', 'add') ? 'add' : 'default';

    $form_state_additions = [];
    $form_state_additions['langcode'] = $target->getId();
    $form_state_additions['content_translation']['source'] = $source;
    $form_state_additions['content_translation']['target'] = $target;
    $form_state_additions['content_translation']['translation_form'] = !$entity->access('update');

    return $this->entityFormBuilder()->getForm($entity, $operation, $form_state_additions);
  }

  /**
   * Builds the edit translation page.
   *
   * @param \Drupal\Core\Language\LanguageInterface $language
   *   The language of the translated values. Defaults to the current content
   *   language.
   * @param \Drupal\Core\Entity\EntityInterface $managed_entity
   *   The managed  entity.
   * @param \Drupal\Core\Entity\EntityInterface $managed_entity_id
   *   The managed entity id.
   *
   * @return array
   *   A processed form array ready to be rendered.
   */
  public function edit(LanguageInterface $language, EntityInterface $managed_entity, EntityInterface $managed_entity_id) {
    $entity = $managed_entity_id;

    $operation = $entity->getEntityType()->hasHandlerClass('form', 'edit') ? 'edit' : 'default';
    $entity = $entity->getTranslation($language->getId());

    $form_state_additions = [];
    // $form_state_additions['langcode'] = $language->getId();
    // $form_state_additions['content_translation']['translation_form'] = TRUE;.
    return $this->entityFormBuilder()->getForm($entity, $operation, $form_state_additions);
  }

}
