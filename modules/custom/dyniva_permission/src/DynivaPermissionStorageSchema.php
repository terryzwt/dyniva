<?php

namespace Drupal\dyniva_permission;

use Drupal\Core\Entity\ContentEntityTypeInterface;
use Drupal\Core\Entity\Sql\SqlContentEntityStorageSchema;
use Drupal\Core\Field\FieldStorageDefinitionInterface;

/**
 * Defines the node schema handler.
 */
class DynivaPermissionStorageSchema extends SqlContentEntityStorageSchema {

  /**
   * {@inheritdoc}
   */
  protected function getEntitySchema(ContentEntityTypeInterface $entity_type, $reset = FALSE) {
    $schema = parent::getEntitySchema($entity_type, $reset);

    $schema['dyniva_permission']['indexes'] += [
      'permission__uid' => ['uid'],
      'permission__tid' => ['tid'],
      'permission__vid' => ['vid'],
      'permission__user' => ['uid','tid'],
    ];

    return $schema;
  }

}
