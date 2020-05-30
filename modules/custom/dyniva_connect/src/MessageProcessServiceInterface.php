<?php

namespace Drupal\dyniva_connect;

use Drupal\dyniva_connect\Entity\Connector;

/**
 * Interface MessageProcessServiceInterface.
 *
 * @package Drupal\dyniva_connect
 */
interface MessageProcessServiceInterface {

  /**
   * 
   * @param ConnectorMessageProcessorInterface $processor
   * @param integer $priority
   */
  public function addProcessor(ConnectorMessageProcessorInterface $processor, $priority = 0);
  
  /**
   * 
   */
  public function getProcessors();
  
  /**
   * 
   * @param Connector $connector
   * @param unknown $app
   */
  public function process(Connector $connector, $app);
  
}
