<?php

namespace Drupal\dyniva_connect\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a Connector type plugin item annotation object.
 *
 * @see \Drupal\dyniva_connect\Plugin\ConnectorTypePluginManager
 * @see plugin_api
 *
 * @Annotation
 */
class ConnectorType extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The label of the plugin.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $label;

}
