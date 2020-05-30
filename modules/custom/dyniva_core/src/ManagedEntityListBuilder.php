<?php

namespace Drupal\dyniva_core;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;

/**
 * Provides a listing of Managed entity entities.
 */
class ManagedEntityListBuilder extends ConfigEntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['label'] = $this->t('Managed entity');
    $header['id'] = $this->t('Machine name');
    $header['entity_type'] = $this->t('Entity type');
    $header['bundle'] = $this->t('Bundle');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $row['label'] = $entity->label();
    $row['id'] = $entity->id();
    $row['entity_type'] = $entity->get('entity_type');
    $row['bundle'] = $entity->get('bundle');
    // You probably want a few more properties here...
    return $row + parent::buildRow($entity);
  }

}
