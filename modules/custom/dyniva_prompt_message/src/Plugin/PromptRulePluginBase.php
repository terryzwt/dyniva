<?php

namespace Drupal\dyniva_prompt_message\Plugin;

use Drupal\Component\Plugin\PluginBase;
use Drupal\dyniva_prompt_message\Entity\PromptRule;
use Drupal\Component\Utility\Crypt;
use Drupal\Component\Serialization\Json;

/**
 * Base class for Prompt rule plugin plugins.
 */
abstract class PromptRulePluginBase extends PluginBase implements PromptRulePluginInterface {

  /**
   * {@inheritdoc}
   */
  public function getMessage(array $context = []) {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigForm() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getKey(PromptRule $rule) {
    return Crypt::hashBase64(Json::encode($rule->getParams()));
  }

  /**
   * {@inheritdoc}
   */
  public function getRules($key = FALSE) {
    $conditions = [
      'type' => $this->getPluginId(),
    ];
    if ($key) {
      $conditions['key'] = $key;
    }
    $rules = \Drupal::entityTypeManager()->getStorage('prompt_rule')->loadByProperties($conditions);
    return $rules;
  }

}
