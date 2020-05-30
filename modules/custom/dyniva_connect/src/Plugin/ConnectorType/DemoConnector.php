<?php
namespace Drupal\dyniva_connect\Plugin\ConnectorType;

use Drupal\dyniva_connect\Plugin\ConnectorTypePluginBase;
use Drupal\dyniva_connect\Entity\Connector;
use Drupal\Component\Utility\Random;
use Drupal\Component\Utility\Crypt;

/**
 * A demo connector.
 * 
 * @ConnectorType(
 *  id = "demo",
 *  label = @Translation("Demo connector")
 * )
 *
 */
class DemoConnector extends ConnectorTypePluginBase{
  /**
   * @inheritdoc
   */
  public static function buildConfigForm($config = array()){
    $form = array();
    
    $form['id'] = array(
      '#type' => 'textfield',
      '#title' => 'Demo id',
      '#required' => true,
      '#default_value' => isset($config['id'])?$config['id']:'',
    );
    
    return $form;
  }
  
  /**
   * @inheritdoc
   */
  public static function processConnect(Connector $connector){
    $user_info = array(
      'openid' => 123456,
//       'openid' => Crypt::randomBytesBase64(),
    );
    return $connector->getConnection($user_info);
  }
  
  /**
   * @inheritdoc
   */
  public static function processMessage(Connector $connector){
  
  }
}