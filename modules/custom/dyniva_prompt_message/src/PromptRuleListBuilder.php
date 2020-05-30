<?php

namespace Drupal\dyniva_prompt_message;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;

/**
 * Provides a listing of Managed entity entities.
 */
class PromptRuleListBuilder extends ConfigEntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['label'] = $this->t('Label');
    $header['id'] = $this->t('Machine name');
    $header['type'] = $this->t('Rule Type');
    $header['message'] = $this->t('Message');
//     $header['key'] = $this->t('Key');
    // $header['params'] = $this->t('Params');.
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $row['label'] = $entity->label();
    $row['id'] = $entity->id();
    $row['type'] = $entity->getType();
    $row['message'] = $entity->getMessage();
//     $row['key'] = $entity->getKey();
    // $row['params'] = Json::encode($entity->getParams());
    // You probably want a few more properties here...
    return $row + parent::buildRow($entity);
  }

}
