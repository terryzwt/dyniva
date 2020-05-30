<?php

namespace Drupal\dyniva_prompt_message\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a Prompt Rule plugin item annotation object.
 *
 * @Annotation
 */
class PromptRule extends Plugin {


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
