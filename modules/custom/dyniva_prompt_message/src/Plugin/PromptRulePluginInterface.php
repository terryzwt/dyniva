<?php

namespace Drupal\dyniva_prompt_message\Plugin;

use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\dyniva_prompt_message\Entity\PromptRule;

/**
 * Defines an interface for Managed entity plugin plugins.
 */
interface PromptRulePluginInterface extends PluginInspectionInterface {

  /**
   * Get prompt message.
   *
   * @param array $context
   *   Prompt context.
   * @return PromptRule[]
   *   Prompt Rule array
   */
  public function getMessage(array $context = []);

  /**
   * Build config form.
   */
  public function buildConfigForm();

  /**
   * Generate rule key.
   */
  public function getKey(PromptRule $rule);

  /**
   * Get rules.
   */
  public function getRules($key = FALSE);

}
