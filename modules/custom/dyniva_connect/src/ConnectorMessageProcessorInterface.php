<?php
namespace Drupal\dyniva_connect;

use Drupal\dyniva_connect\Entity\Connector;

/**
 * Interface ConnectorMessageProcessorInterface.
 *
 * @package Drupal\dyniva_connect
 */
interface ConnectorMessageProcessorInterface {

  /**
   * 
   * @param Connector $connector
   * @param unknown $app
   */
  public function process(Connector $connector, $app);
  
  /**
   * 
   * @param Connector $connector
   */
  public function apply(Connector $connector);
  
}