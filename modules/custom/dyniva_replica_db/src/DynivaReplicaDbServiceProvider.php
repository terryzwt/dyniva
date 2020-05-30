<?php

namespace Drupal\dyniva_replica_db;

use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;
use Drupal\Core\DependencyInjection\ServiceProviderBase;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Overrides the class for the menu link tree.
 */
class DynivaReplicaDbServiceProvider extends ServiceProviderBase {

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) {
    if($container->hasDefinition("statistics.storage.node")){
      $definition = $container->getDefinition("statistics.storage.node");
      $definition->setClass('Drupal\dyniva_replica_db\NodeStatisticsDatabaseStorage');
      $definition->setArgument(3, new Reference('database.replica'));
    }
    if($container->hasDefinition("statistics.storage.taxonomy")){
      $definition = $container->getDefinition("statistics.storage.taxonomy");
      $definition->setClass('Drupal\dyniva_replica_db\TaxonomyStatisticsDatabaseStorage');
      $definition->setArgument(3, new Reference('database.replica'));
    }
  }

}
