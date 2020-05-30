<?php

namespace Drupal\dyniva_core\Plugin;

use Drupal\Component\Plugin\PluginBase;
use Drupal\dyniva_core\Entity\ManagedEntity;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Url;

/**
 * Base class for Managed entity plugin plugins.
 */
abstract class ManagedEntityPluginBase extends PluginBase implements ManagedEntityPluginInterface {

  /**
   * {@inheritdoc}
   */
  public function buildPage(ManagedEntity $managedEntity, EntityInterface $entity) {
    return [
      '#markup' => 'TODO',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getPageTitle(ManagedEntity $managedEntity, EntityInterface $entity) {
    return $entity->label() . ' : ' . $this->pluginDefinition['label'];
  }

  /**
   * {@inheritdoc}
   */
  public function getPagePath(ManagedEntity $managedEntity) {
    return $this->getPluginId();
  }

  /**
   * {@inheritdoc}
   */
  public function getPageRequirements(ManagedEntity $managedEntity) {
    return ['_permission' => "manage ccms {$managedEntity->id()} {$this->getPluginId()}"];
  }

  /**
   * {@inheritdoc}
   */
  public function applyOperationLinks(ManagedEntity $managedEntity, EntityInterface $entity, &$operations) {
    $url = Url::fromRoute("dyniva_core.managed_entity.{$managedEntity->id()}.{$this->getPluginId()}_page", ['managed_entity_id' => $entity->id()], []);
    $account = \Drupal::currentUser();
    $access = $account->hasPermission("manage ccms {$managedEntity->id()} {$this->getPluginId()}");
    if ($access) {
      $operations[$this->getPluginId()] = [
        'title' => t($this->pluginDefinition['label']->getUntranslatedString()),
        'url' => $url,
        'weight' => count($operations),
      ];
    }

  }

  /**
   * {@inheritdoc}
   */
  public function isMenuTask(ManagedEntity $managedEntity) {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function isMenuAction(ManagedEntity $managedEntity) {
    return FALSE;
  }

}
