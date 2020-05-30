<?php

namespace Drupal\dyniva_connect_wechat;


use Drupal\dyniva_connect\ConnectorMessageProcessorInterface;
use Drupal\dyniva_connect\Entity\Connector;

/**
 * Class QyDemoMessageProcessor.
 *
 * @package Drupal\dyniva_connect_wechat
 */
class QyDemoMessageProcessor implements ConnectorMessageProcessorInterface{
  
  /**
   *
   * @inheritdoc
   */
  public function process(Connector $connector, $app){
    $app->server->push(function ($message) use ($connector){
      return "{$connector->getName()} 欢迎您!";
    });
  }
  
  /**
   *
   * @inheritdoc
   */
  public function apply(Connector $connector){
    return $connector->getType() == 'wechat_qy';
  }

}
