<?php

namespace Drupal\dyniva_core;

use Drupal\Core\Entity\RevisionableInterface;
use Drupal\Core\Entity\TranslatableRevisionableInterface;

class TransliterationHelper {

  /**
   * Returns the ID of the latest revision translation of the specified entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface|\Drupal\Core\Entity\RevisionableInterface $entity
   *   The default revision of the entity being converted.
   * @param string $langcode
   *   The language of the revision translation to be loaded.
   *
   * @return \Drupal\Core\Entity\EntityInterface|\Drupal\Core\Entity\RevisionableInterface
   *   The latest translation-affecting revision for the specified entity, or
   *   just the latest revision, if the specified entity is not translatable or
   *   does not have a matching translation yet.
   */
  public static function getLatestTranslationAffectedRevision(RevisionableInterface $entity, $langcode) {
    $revision = NULL;
    $storage = \Drupal::entityTypeManager()->getStorage($entity->getEntityTypeId());
    
    if ($entity instanceof TranslatableRevisionableInterface && $entity->isTranslatable()) {
      /** @var \Drupal\Core\Entity\TranslatableRevisionableStorageInterface $storage */
      $revision_id = $storage->getLatestTranslationAffectedRevisionId($entity->id(), $langcode);
      
      // If the latest translation-affecting revision was a default revision, it
      // is fine to load the latest revision instead, because in this case the
      // latest revision, regardless of it being default or pending, will always
      // contain the most up-to-date values for the specified translation. This
      // provides a BC behavior when the route is defined by a module always
      // expecting the latest revision to be loaded and to be the default
      // revision. In this particular case the latest revision is always going
      // to be the default revision, since pending revisions would not be
      // supported.
      /** @var \Drupal\Core\Entity\TranslatableRevisionableInterface $revision */
      $revision = $revision_id ? self::loadRevision($entity, $revision_id) : NULL;
      if (!$revision || ($revision->wasDefaultRevision() && !$revision->isDefaultRevision())) {
        $revision = NULL;
      }
    }
    
    // Fall back to the latest revisions if no affected revision for the current
    // content language could be found. This is acceptable as it means the
    // entity is not translated. This is the correct logic also on monolingual
    // sites.
    if (!isset($revision)) {
      $revision_id = $storage->getLatestRevisionId($entity->id());
      $revision = self::loadRevision($entity, $revision_id);
    }
    
    return $revision;
  }
  
  /**
   * Loads the specified entity revision.
   *
   * @param \Drupal\Core\Entity\EntityInterface|\Drupal\Core\Entity\RevisionableInterface $entity
   *   The default revision of the entity being converted.
   * @param string $revision_id
   *   The identifier of the revision to be loaded.
   *
   * @return \Drupal\Core\Entity\EntityInterface|\Drupal\Core\Entity\RevisionableInterface
   *   An entity revision object.
   */
  public static function loadRevision(RevisionableInterface $entity, $revision_id) {
    // We explicitly perform a loose equality check, since a revision ID may
    // be returned as an integer or a string.
    if (!empty($revision_id) && $entity->getLoadedRevisionId() != $revision_id) {
      $storage = \Drupal::entityTypeManager()->getStorage($entity->getEntityTypeId());
      return $storage->loadRevision($revision_id);
    }
    else {
      return $entity;
    }
  }
}

