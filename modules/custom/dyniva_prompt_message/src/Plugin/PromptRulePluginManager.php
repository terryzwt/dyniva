<?php

namespace Drupal\dyniva_prompt_message\Plugin;

use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\dyniva_prompt_message\Entity\PromptRule;
use Drupal\Component\Render\FormattableMarkup;

/**
 * Provides the Managed entity plugin plugin manager.
 */
class PromptRulePluginManager extends DefaultPluginManager {

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
    parent::__construct('Plugin/PromptRule', $namespaces, $module_handler, 'Drupal\dyniva_prompt_message\Plugin\PromptRulePluginInterface', 'Drupal\dyniva_prompt_message\Annotation\PromptRule');

    $this->alterInfo('dyniva_prompt_message_prompt_rule_plugin_info');
    $this->setCacheBackend($cache_backend, 'dyniva_prompt_message_prompt_rule_plugins');
  }

  /**
   * Prompt message.
   *
   * @param string $plugin_id
   *   Plugin id.
   * @param array $context
   *   Prompt context.
   * @param bool $status_message
   *   Show as stattus message flag.
   *
   * @return string
   *   Message.
   */
  public function prompt($plugin_id, array $context = [], $status_message = TRUE) {
    $plugin = $this->createInstance($plugin_id);
    $messages = $plugin->getMessage($context);
    if(!empty($messages) && $status_message){
      foreach ($messages as $rule) {
        if($rule instanceof PromptRule){
          $class = [$rule->getMessageType()];
          if($rule->getForce()){
            $class[] = "messages--force";
          }
          $markup = new FormattableMarkup($rule->getMessage(), []);
          drupal_set_message($markup, implode(" ", $class));
        }
      }
    }
    return $messages;
  }

  /**
   * Get plugin list.
   *
   * @return \Drupal\Core\Plugin\mixed[]
   *   Options list.
   */
  public function getSelectOptions() {
    $options = [];
    foreach ($this->getDefinitions() as $id => $item) {
      $options[$id] = $item['label'];
    }
    return $options;
  }

  /**
   * Get plugin config form.
   *
   * @param string $type
   *   Plugin id.
   * @param object $config
   *   Plugin params data.
   */
  public function getConfigForm($type, $config) {
    if ($this->hasDefinition($type)) {
      $plugin = $this->createInstance($type);
      return $plugin->buildConfigForm($config);
    }
    return [];
  }

  /**
   * Get query key.
   *
   * @param \Drupal\dyniva_prompt_message\Entity\PromptRule $rule
   *   Prompt rule item.
   *
   * @return unknown|null
   *   Query key.
   */
  public function getKey(PromptRule $rule) {
    if ($this->hasDefinition($rule->getType())) {
      $plugin = $this->createInstance($rule->getType());
      return $plugin->getKey($rule);
    }
    return NULL;
  }

}
