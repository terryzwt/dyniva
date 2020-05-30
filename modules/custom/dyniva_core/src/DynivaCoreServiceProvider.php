<?php

namespace Drupal\dyniva_core;

use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;
use Drupal\Core\DependencyInjection\ServiceProviderBase;
use Drupal\Core\DependencyInjection\ContainerBuilder;

/**
 * Overrides the class for the menu link tree.
 */
class DynivaCoreServiceProvider extends ServiceProviderBase {

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) {

    try {
      $definition = $container->getDefinition('menu_trail_by_path.path_helper');
      $definition->setClass('Drupal\dyniva_core\Path\CurrentPathHelper');
    }
    catch (ServiceNotFoundException $e) {

    }
    try {
      $definition = $container->getDefinition('menu_trail_by_path.menu_helper');
      $definition->setClass('Drupal\dyniva_core\Menu\MenuTreeStorageMenuHelper');
    }
    catch (ServiceNotFoundException $e) {

    }
    try {
      $definition = $container->getDefinition('queue.database');
      $definition->setClass('Drupal\dyniva_core\Queue\CcmsQueueDatabaseFactory');
    }
    catch (ServiceNotFoundException $e) {

    }
    try {
      $definition = $container->getDefinition('plugin.manager.action');
      $definition->setClass('Drupal\dyniva_core\Action\CcmsActionManager');
    }
    catch (ServiceNotFoundException $e) {

    }

    try {
      $definition = $container->getDefinition('lightning.media_helper');
      $definition->setClass('Drupal\dyniva_core\MediaHelper');
    }
    catch (ServiceNotFoundException $e) {

    }

  }

}
