<?php

namespace Drupal\dyniva_core\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a Managed entity plugin item annotation object.
 *
 * @see \Drupal\dyniva_core\Plugin\ManagedEntityPluginManager
 * @see plugin_api
 *
 * @Annotation
 */
class ManagedEntityPlugin extends Plugin {


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

  /**
   * The weight of the plugin.
   *
   * @var int
   */
  public $weight;

}
