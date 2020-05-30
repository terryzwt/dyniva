<?php

namespace Drupal\dyniva_core\Action;

use Drupal\Core\Action\ActionManager;

/**
 * Provides an Action plugin manager.
 *
 * @see \Drupal\Core\Annotation\Action
 * @see \Drupal\Core\Action\ActionInterface
 * @see \Drupal\Core\Action\ActionBase
 */
class CcmsActionManager extends ActionManager {

  /**
   * {@inheritdoc}
   */
  public function getDefinitionsByType($type) {
    return array_filter($this->getDefinitions(), function ($definition) use ($type) {
      return $definition['type'] === $type || empty($definition['type']);
    });
  }

}
