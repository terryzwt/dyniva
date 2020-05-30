<?php
namespace Drupal\dyniva_connect_wechat\Plugin\ConnectorType;

use Drupal\dyniva_connect\Plugin\ConnectorTypePluginBase;
use Drupal\dyniva_connect\Entity\Connector;
use EasyWeChat\Factory;

/**
 * A demo connector.
 * 
 * @ConnectorType(
 *  id = "wechat_mp",
 *  label = @Translation("Wechat mp connector")
 * )
 *
 */
class WechatMpConnector extends ConnectorTypePluginBase{
  /**
   * @inheritdoc
   */
  public static function buildConfigForm($config = array()){
    $form = array();
    
    $form['app_id'] = array(
      '#type' => 'textfield',
      '#title' => 'App id',
      '#required' => true,
      '#default_value' => isset($config['app_id'])?$config['app_id']:'',
    );
    $form['secret'] = array(
      '#type' => 'textfield',
      '#title' => 'App secret',
      '#required' => true,
      '#default_value' => isset($config['secret'])?$config['secret']:'',
    );
    $form['token'] = array(
      '#type' => 'textfield',
      '#title' => 'Token',
      '#required' => true,
      '#default_value' => isset($config['token'])?$config['token']:'',
    );
    $form['aes_key'] = array(
      '#type' => 'textfield',
      '#title' => 'Aes key',
      '#required' => false,
      '#default_value' => isset($config['aes_key'])?$config['aes_key']:'',
    );
    $form['debug'] = array(
      '#type' => 'select',
      '#title' => 'Debug mode',
      '#required' => true,
      '#default_value' => isset($config['debug'])?$config['debug']:0,
      '#options' => array(
        0 => 'Close',
        1 => 'Open',
      ),
    );
    $form['msg_subscribe'] = array(
      '#type' => 'textarea',
      '#title' => 'Subscribe message',
      '#required' => false,
      '#default_value' => isset($config['msg_subscribe'])?$config['msg_subscribe']:'',
    );
    $form['msg_default'] = array(
      '#type' => 'textarea',
      '#title' => 'Default reply message',
      '#required' => false,
      '#default_value' => isset($config['msg_default'])?$config['msg_default']:'',
    );
    
    
    return $form;
  }
  
  /**
   * @inheritdoc
   */
  public static function processConnect(Connector $connector){
    
    $wechat = static::getWechat($connector);
    $request = \Drupal::request();
    //消息登录模式
    if($request->get('openid')){
      $userInfo = $wechat->user->get($request->get('openid'));
    }elseif($code = $request->get('code')){
      $userInfo = $wechat->oauth->user()->toArray();
    }else{
      $redirect = $wechat->oauth->scopes(['snsapi_userinfo'])->redirect(\Drupal::request()->getUri());
      return $redirect;
    }
    
    if(!empty($userInfo)){
      $userInfo['openid'] = $userInfo['id'];
      if(!empty($userInfo['original'])){
        $userInfo = $userInfo['original'];
      }
      $connection = $connector->getConnection($userInfo);
      if($connection){
        if(\Drupal::currentUser()->isAuthenticated()){
          $connection->setOwnerId(\Drupal::currentUser()->id());
        }
        $connection->save();
        return $connection;
      }
    }
    
    return false;
  }
  
  /**
   * @inheritdoc
   */
  public static function processMessage(Connector $connector){
    global $base_url;
    $sites_path = \Drupal::service('site.path');
    
    $config = $connector->getConfigData();
    $wechat = static::getWechat($connector);
    
    \Drupal::service('dyniva_connect.message_process')->process($connector,$wechat);
    
    return $wechat->server->serve();
  }
  
  /**
   * 
   * @param Connector $connector
   * @return \EasyWeChat\OfficialAccount\Application
   */
  public static function getWechat(Connector $connector){
    $config = $connector->getConfigData();
    $wechat = Factory::officialAccount($config);
    
    return $wechat;
  }
}