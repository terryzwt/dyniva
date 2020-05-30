<?php
namespace Drupal\dyniva_connect_wechat\Plugin\ConnectorType;

use Drupal\dyniva_connect\Plugin\ConnectorTypePluginBase;
use Drupal\dyniva_connect\Entity\Connector;
use Drupal\Component\Serialization\Json;
use EasyWeChat\Factory;
use Symfony\Component\HttpFoundation\JsonResponse;
use Drupal\user\Entity\User;
use Drupal\Core\Access\CsrfRequestHeaderAccessCheck;

/**
 * A demo connector.
 * 
 * @ConnectorType(
 *  id = "wechat_mini",
 *  label = @Translation("Wechat mini program connector")
 * )
 *
 */
class WechatMiniConnector extends ConnectorTypePluginBase{
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
      '#required' => false,
      '#default_value' => isset($config['token'])?$config['token']:'',
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
    
    
    return $form;
  }
  
  /**
   * @inheritdoc
   */
  public static function processConnect(Connector $connector){
    $request = \Drupal::request();
    $data = [
      'sessionName' => false,
      'sessionId' => false,
      'uuid' => false,
      'token' => false,
    ];
    
    if(\Drupal::currentUser()->isAuthenticated()){
      $connection = $connector->getConnectionByUserId(\Drupal::currentUser()->id());
    }else{
      $wechat = static::getWechat($connector);
      
      if($code = $request->get('code')){
        $userInfo = $wechat->auth->session($code);
      }elseif($openid = $request->get('openid') && $detail = $request->get('detail')){
        $detail = Json::decode($detail);
        $userInfo['openid'] = $openid;
        $userInfo['nickname'] = $detail['nickName'];
        $userInfo['sex'] = $detail['gender'];
        $userInfo['city'] = $detail['city'];
        $userInfo['country'] = $detail['country'];
        $userInfo['province'] = $detail['province'];
        $userInfo['headimgurl'] = $detail['avatarUrl'];
      }
      
      if(!empty($userInfo)){
        $connection = $connector->getConnection($userInfo);
        if($connection){
          if(!$connection->getOwnerId() && !empty($openid)){
            $user = user_load_by_name($openid);
            if(empty($user)){
              $user = User::create([
                'name' => $connection->openid->value,
                'nickname' => $connection->nickname->value,
                'avatarurl' => $connection->headimgurl->value,
                'password' => user_password(),
                'status' => 1
              ]);
              $user->save();
            }
            
            $connection->setOwner($user);
            $connection->save();
          }
        }
      }
    }
    if(\Drupal::currentUser()->isAnonymous() && $account = $connection->getOwner()){
      user_login_finalize($account);
    }
    if($connection){
      $data['openid'] = $connection->openid->value;
      $data['nickName'] = $connection->nickname->value;
      $data['avatarUrl'] = $connection->headimgurl->value;
      $data['uuid'] = $connection->getOwner()->uuid();
    }
    if(\Drupal::currentUser()->isAuthenticated()){
      $data['sessionName'] = $request->getSession()->getName();
      $data['sessionId'] = $request->getSession()->getId();
      $data['token'] =\Drupal::service('csrf_token')->get(CsrfRequestHeaderAccessCheck::TOKEN_KEY);
    }
    $response = new JsonResponse($data);
    return $response;
  }
  /**
   * @inheritdoc
   */
  public static function processMessage(Connector $connector){
    global $base_url;
    $sites_path = \Drupal::service('site.path');
    
    $wechat = static::getWechat($connector);
    $wechat->server->push("您好！欢迎使用 EasyWeChat");
    $response = $wechat->server->serve();
    return $response;
  }
  /**
   * 
   * @param Connector $connector
   * @return \EasyWeChat\MiniProgram\Application
   */
  public static function getWechat(Connector $connector){
    $config = $connector->getConfigData();
    $wechat = Factory::miniProgram($config);
    
    return $wechat;
  }
}