<?php

namespace Drupal\dyniva_connect\Plugin;

use Drupal\Component\Plugin\PluginBase;
use Drupal\dyniva_connect\Entity\Connector;
use Drupal\dyniva_connect\Entity\Connection;
use Drupal\user\Entity\User;

/**
 * Base class for Connector type plugin plugins.
 */
abstract class ConnectorTypePluginBase extends PluginBase implements ConnectorTypePluginInterface {

  /**
   * @inheritdoc
   */
  public static function buildConfigForm(){
    return array();
  }
  
  /**
   * @inheritdoc
   */
  public static function processConnect(Connector $connector){
    
  }
  
  /**
   * @inheritdoc
   */
  public static function processMessage(Connector $connector){
    
  }

  /**
   * @inheritdoc
   */
  public static function buildBindForm(Connector $connector,Connection $connection){
    
    $form = array();
    
    $form['user_name'] = array(
      '#type' => 'textfield',
      '#title' => 'User Name',
      '#required' => true,
      '#default_value' => '',
    );
    $form['password'] = array(
      '#type' => 'password',
      '#title' => 'Password',
      '#required' => true,
      '#default_value' => '',
    );
    
    return $form;
  }
  
  /**
   * @inheritdoc
   */
  public static function getBindUser($form_values){
    $uid = \Drupal::service('user.auth')->authenticate($form_values['user_name'], $form_values['password']);
    if($uid){
      return User::load($uid);
    }
  }
  
}
