<?php

namespace Drupal\dyniva_core\Plugin;

use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\dyniva_core\Entity\ManagedEntity;

/**
 * Provides the Managed entity plugin plugin manager.
 */
class ManagedEntityPluginManager extends DefaultPluginManager {

  /**
   * Constructor for ManagedEntityPluginManager objects.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler to invoke the alter hook with.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct('Plugin/ManagedEntity', $namespaces, $module_handler, 'Drupal\dyniva_core\Plugin\ManagedEntityPluginInterface', 'Drupal\dyniva_core\Annotation\ManagedEntityPlugin');

    $this->alterInfo('dyniva_core_managed_entity_plugin_info');
    $this->setCacheBackend($cache_backend, 'dyniva_core_managed_entity_plugin_plugins');
  }

  /**
   * Managed entity has plugin enabled.
   *
   * @param \Drupal\dyniva_core\Entity\ManagedEntity $entity
   *   Managed entity.
   * @param string $plugin_id
   *   Plugin id.
   */
  public static function isPluginEnable(ManagedEntity $entity, $plugin_id) {
    $enable_plugins = $entity->get('plugins') ? $entity->get('plugins') : [];
    return !empty($enable_plugins[$plugin_id]);
  }

}
