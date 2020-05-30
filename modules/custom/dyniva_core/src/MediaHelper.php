<?php

namespace Drupal\dyniva_core;

use Drupal\lightning_media\MediaHelper as BaseHelper;

/**
 * Media Helper.
 */
class MediaHelper extends BaseHelper {

  /**
   * {@inheritdoc}
   */
  public function createFromInput($value, array $bundles = []) {

    $init = [
      'bundle' => $this->getBundleFromInput($value, TRUE, $bundles)->id(),
    ];
    if (!is_object($value)) {
      $file = file_load($value);
    }
    if (!empty($file)) {
      $init['title'] = $file->getFilename();
      $init['name'] = $file->getFilename();
    }
    /** @var \Drupal\media_entity\MediaInterface $entity */
    $entity = $this->entityTypeManager
      ->getStorage('media')
      ->create($init);

    $field = static::getSourceField($entity);
    if ($field) {
      $field->setValue($value);
    }
    return $entity;
  }

}
