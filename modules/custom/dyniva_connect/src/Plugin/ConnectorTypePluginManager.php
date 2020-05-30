<?php

namespace Drupal\dyniva_connect\Plugin;

use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Component\Plugin\Factory\DefaultFactory;
use Drupal\dyniva_connect\Entity\Connector;
use Drupal\dyniva_connect\Entity\Connection;

/**
 * Provides the Connector type plugin plugin manager.
 */
class ConnectorTypePluginManager extends DefaultPluginManager {

  /**
   * Constructor for ConnectorTypePluginManager objects.
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
    parent::__construct('Plugin/ConnectorType', $namespaces, $module_handler, 'Drupal\dyniva_connect\Plugin\ConnectorTypePluginInterface', 'Drupal\dyniva_connect\Annotation\ConnectorType');

    $this->alterInfo('dyniva_connect_connector_type_plugin_info');
    $this->setCacheBackend($cache_backend, 'dyniva_connect_connector_type_plugin_plugins');
  }

  /**
   * user connect
   * 
   * @param Connector $connector
   * @return boolean
   */
  public function processConnect(Connector $connector){
    $type = $connector->getType();
    $plugin_definition = $this->getDefinition($type, FALSE);
    if ($plugin_class = $this->getPluginClass($type)) {
      return $plugin_class::processConnect($connector);
    }
    return false;
  }
  /**
   * message connect
   * 
   * @param Connector $connector
   * @return boolean
   */
  public function processMessage(Connector $connector){
    $type = $connector->getType();
    $plugin_definition = $this->getDefinition($type, FALSE);
    if ($plugin_class = $this->getPluginClass($type)) {
      return $plugin_class::processMessage($connector);
    }
    return false;
  }
  /**
   * Get plugin list
   *  
   * @return \Drupal\Core\Plugin\mixed[]
   */
  public function getSelectOptions(){
    $options = array();
    foreach ($this->getDefinitions() as $id => $item){
      $options[$id] = $item['label'];
    }
    return $options;
  }
  /**
   * Get plugin config form.
   * 
   * @param unknown $type
   * @param unknown $config
   */
  public function getConfigForm($type,$config){
    $plugin_definition = $this->getDefinition($type, FALSE);
    if ($plugin_class = $this->getPluginClass($type)) {
      return $plugin_class::buildConfigForm($config);
    }
    return array();
  }
  /**
   * Get user bind form.
   * 
   * @param Connector $connector
   * @param Connection $connection
   */
  public function buildBindForm(Connector $connector,Connection $connection){
    $type = $connector->getType();
    $plugin_definition = $this->getDefinition($type, FALSE);
    if ($plugin_class = $this->getPluginClass($type)) {
      return $plugin_class::buildBindForm($connector,$connection);
    }
    return array();
  }
  
  /**
   * Get bind form user.
   * 
   * @param Connector $connector
   * @param unknown $form_values
   */
  public function getBindUser(Connector $connector,$form_values){
    $type = $connector->getType();
    $plugin_definition = $this->getDefinition($type, FALSE);
    if ($plugin_class = $this->getPluginClass($type)) {
      return $plugin_class::getBindUser($form_values);
    }
    return false;
  }
  /**
   * Get plugin class.
   */
  public function getPluginClass($plugin_id){
    $plugin_definition = $this->getDefinition($plugin_id, FALSE);
    if (!empty($plugin_definition['class'])) {
      $plugin_class = DefaultFactory::getPluginClass($plugin_id, $plugin_definition);
      return $plugin_class;
    }
    return false;
  }
}
