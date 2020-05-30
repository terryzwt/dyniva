<?php
namespace Drupal\dyniva_connect_wechat\Plugin\ConnectorType;

use Drupal\dyniva_connect\Plugin\ConnectorTypePluginBase;
use Drupal\dyniva_connect\Entity\Connector;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Component\Serialization\Json;
use Drupal\dyniva_connect\Entity\Connection;
use EasyWeChat\Factory;

/**
 * A demo connector.
 * 
 * @ConnectorType(
 *  id = "wechat_qy",
 *  label = @Translation("Wechat qy connector")
 * )
 *
 */
class WechatQyConnector extends ConnectorTypePluginBase{
  /**
   * @inheritdoc
   */
  public static function buildConfigForm($config = array()){
    $form = array();
    
    $form['appid'] = array(
      '#type' => 'textfield',
      '#title' => 'App id',
      '#required' => true,
      '#default_value' => isset($config['appid'])?$config['appid']:'',
    );
    $form['appsecret'] = array(
      '#type' => 'textfield',
      '#title' => 'App secret',
      '#required' => true,
      '#default_value' => isset($config['appsecret'])?$config['appsecret']:'',
    );
    $form['token'] = array(
      '#type' => 'textfield',
      '#title' => 'Token',
      '#required' => true,
      '#default_value' => isset($config['token'])?$config['token']:'',
    );
    $form['agentid'] = array(
      '#type' => 'textfield',
      '#title' => 'Agentid',
      '#required' => true,
      '#default_value' => isset($config['agentid'])?$config['agentid']:'',
    );
    $form['encodingaeskey'] = array(
      '#type' => 'textfield',
      '#title' => 'Aes key',
      '#required' => false,
      '#default_value' => isset($config['encodingaeskey'])?$config['encodingaeskey']:'',
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
    global $base_url;
    $sites_path = \Drupal::service('site.path');
    
    $wechat = static::getWechat($connector);
    $request = \Drupal::request();
    //消息登录模式
    if($openid = $request->get('userid')){
      $userInfo = $wechat->getUserInfo($openid,'zh_CN');
    }elseif($code = $request->get('code')){
      $data = $wechat->getUserId($code);
      dyniva_connect_wechat_connector_wechat_logger($wechat, $data);
      if(isset($data['UserId'])){
        $userInfo = $wechat->getUserInfo($data['UserId']);
        dyniva_connect_wechat_connector_wechat_logger($wechat,$userInfo);
        $userInfo['openid'] = $userInfo['userid'];
        $userInfo['nickname'] = $userInfo['name'];
        $userInfo['sex'] = $userInfo['gender'];
        $userInfo['city'] = $userInfo['position'];
        $userInfo['province'] = Json::encode($userInfo['department']);
        $userInfo['country'] = $userInfo['mobile'];
        $userInfo['headimgurl'] = $userInfo['avatar'];
        $userInfo['unionid'] = $userInfo['email'];
        $userInfo['remark'] = Json::encode($userInfo['extattr']);
      }
      
    }else{
      $url = $wechat->getOauthRedirect(\Drupal::request()->getUri());
      $redirect = new RedirectResponse($url);
      $redirect->send();
      return;
    }
    
    if(!empty($userInfo)){
      $connection = $connector->getConnection($userInfo);
      if(!$connection->getOwnerId() && !empty($userInfo['userid'])){
        if($account = user_load_by_name($userInfo['userid'])){
          $connection->setOwner($account);
          $connection->save();
        }
      }
      if(!$connection->getOwnerId() && !empty($userInfo['email'])){
        if($account = user_load_by_mail($userInfo['email'])){
          $connection->setOwner($account);
          $connection->save();
        }else{
          $query = \Drupal::entityQuery('node')
          ->condition('type','staff')
          ->condition('field_emaillim1req1',$userInfo['email']);
          $ids = $query->execute();
          $id = reset($ids);
          $profile = node_load($id);
          if($account = $profile->field_userrefnolim1->entity){
            $connection->setOwner($account);
            $connection->save();
          }
        }
      }
     if(!$connection->getOwnerId() && !empty($userInfo['mobile'])){
        $query = \Drupal::entityQuery('node')
          ->condition('type','staff')
        ->condition('field_org_telephone',$userInfo['mobile']);
        $ids = $query->execute();
        $id = reset($ids);
        $profile = node_load($id);
        if($account = $profile->field_userrefnolim1->entity){
          $connection->setOwner($account);
          $connection->save();
        }
      }
      return $connection;
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
    $wechat->valid();
    $wechat->getRev();
    
    \Drupal::service('dyniva_connect.message_process')->process($connector,$wechat);
    
    if (!is_array($wechat->Message(''))) {
      $type = $wechat->getRev()->getRevType();
      $openid = $wechat->getRevFrom();
      switch ($type) {
        case Wechat::MSGTYPE_EVENT:
          $event = $wechat->getRevEvent();
          // 关注事件
          if ($event['event'] == Wechat::EVENT_SUBSCRIBE) {
            if (!empty($config['msg_subscribe'])) {
              $wechat->text($config['msg_subscribe']);
            }
          }
          // 点击式菜单
          else if ($event['event'] == 'CLICK') {
            
          }
          // 地理位置事件
          else if ($event['event'] == Wechat::EVENT_LOCATION) {
            
          }
          break;
        case Wechat::MSGTYPE_TEXT:
          if (!empty($config['msg_default'])) {
            $wechat->text($config['msg_default']);
          }
    
          break;
      }
    }
    if ($type != Wechat::MSGTYPE_EVENT && !is_array($wechat->Message(''))) {
      $wechat->text("{$connector->getName()} 欢迎您!");
    }
    $wechat->reply();
    exit;
  }
  
  /**
   * 
   * @param Connector $connector
   * @return \EasyWeChat\Work\Application
   */
  public static function getWechat(Connector $connector){
    $config = $connector->getConfigData();
    $wechat = Factory::work($config);
    
    return $wechat;
  }
  
  /**
   * @inheritdoc
   */
  public static function buildBindForm(Connector $connector,Connection $connection){
  
    $form = array();
  
    $form['email'] = array(
      '#type' => 'textfield',
      '#title' => 'User email',
      '#required' => true,
      '#default_value' => '',
    );
  
    $response = new RedirectResponse(Url::fromUserInput('/user/login')->setAbsolute()->toString());
    $response->send();
    return $form;
  }
  
  /**
   * @inheritdoc
   */
  public static function getBindUser($form_values){
//     return user_load_by_mail($form_values['email']);
    return false;
  }
}