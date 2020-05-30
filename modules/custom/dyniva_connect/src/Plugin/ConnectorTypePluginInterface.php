<?php

namespace Drupal\dyniva_connect\Plugin;

use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\dyniva_connect\Entity\Connector;
use Drupal\dyniva_connect\Entity\Connection;

/**
 * Defines an interface for Connector type plugin plugins.
 */
interface ConnectorTypePluginInterface extends PluginInspectionInterface {

  /**
   * 获取配置表单
   */
  public static function buildConfigForm();
  
  /**
   * 获取绑定表单
   */
  public static function buildBindForm(Connector $connector,Connection $connection);
  
  /**
   * 获取绑定用户
   */
  public static function getBindUser($form_values);
  
  /**
   * 处理用户接入
   * @param Connector $connector
   */
  public static function processConnect(Connector $connector);
  /**
   * 处理消息接入
   * @param Connector $connector
   */
  public static function processMessage(Connector $connector);
}
