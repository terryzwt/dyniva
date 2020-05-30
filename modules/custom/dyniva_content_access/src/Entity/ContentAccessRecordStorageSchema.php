<?php

namespace Drupal\dyniva_content_access\Entity;

use Drupal\Core\Entity\ContentEntityTypeInterface;
use Drupal\Core\Entity\Sql\SqlContentEntityStorageSchema;
use Drupal\Core\Field\FieldStorageDefinitionInterface;

/**
 * Defines the node schema handler.
 */
class ContentAccessRecordStorageSchema extends SqlContentEntityStorageSchema {

  /**
   * {@inheritdoc}
   */
  protected function getEntitySchema(ContentEntityTypeInterface $entity_type, $reset = FALSE) {
    $schema = parent::getEntitySchema($entity_type, $reset);

    $schema['content_access_record']['indexes'] += [
      'record__parent' => ['entity_type', 'entity_id'],
      'record__type' => ['record_type'],
    ];

    return $schema;
  }
}
