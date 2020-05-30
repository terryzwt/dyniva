<?php

namespace Drupal\dyniva_editor_panelizer;

use Drupal\Core\DependencyInjection\ServiceProviderBase;
use Drupal\Core\DependencyInjection\ContainerBuilder;

/**
 * {@inheritdoc}
 */
class PanelsIpeCcmsServiceProvider extends ServiceProviderBase {

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) {
    $definition = $container->getDefinition('panelizer');
    $definition->setClass('Drupal\dyniva_editor_panelizer\Panelizer');
  }

}
