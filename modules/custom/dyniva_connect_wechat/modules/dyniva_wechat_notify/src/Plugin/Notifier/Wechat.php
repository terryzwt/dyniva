<?php

namespace Drupal\dyniva_wechat_notify\Plugin\Notifier;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\message_notify\Plugin\Notifier\MessageNotifierBase;
use Drupal\message_notify\Exception\MessageNotifyException;
use function GuzzleHttp\json_decode;
use Drupal\Component\Serialization\Json;
use Drupal\Component\Utility\Html;
use Drupal\Component\Render\PlainTextOutput;

/**
 * Wechat notifier.
 *
 * @Notifier(
 *   id = "wechat",
 *   title = @Translation("Wechat"),
 *   description = @Translation("Send messages via Wechat Template Message"),
 *   viewModes = {
 *     "notify_wechat_template"
 *   }
 * )
 */
class Wechat extends MessageNotifierBase {
  
  use StringTranslationTrait;
  
  /**
   * {@inheritdoc}
   */
  public function deliver(array $output = []) {
    
    try {
      $data_str = strip_tags(Html::decodeEntities($output['notify_wechat_template']));
      $data_str = str_replace('&nbsp;',' ',$data_str);
      $message_data = Json::decode($data_str);
      $template_id = $message_data['id'];
      $template_data = $message_data['data'];
      $url = false;
      if(!empty($message_data['url'])){
        $url = $message_data['url'];
      }
      if(empty($template_id) || empty($template_data)){
        return false;
      }
      
      /** @var \Drupal\user\UserInterface $account */
      $account = $this->message->getOwner();
      $connection = false;
      
      $query = \Drupal::entityQuery('connection')
      ->condition('user_id',$account->id());
      
      $ids = $query->execute();
      foreach ($ids as $id){
        $connection = entity_load('connection', $id);
        $connector = $connection->getConnector();
        if (!empty($connection) && $connector && in_array($connector->getType(),['wechat_mp','wechat_qy'])) {
          if ($plugin_class = \Drupal::service('plugin.manager.connector_type_plugin')->getPluginClass($connector->getType())) {
            $wechat = $plugin_class::getWechat($connector);
            $wechat->template_message->send([
              'touser' => $connection->getOpenid(),
              'template_id' => $template_id,
              'url' => $url,
              'data' => $template_data,
            ]);
          }
        }
      }
    }
    catch (\Exception $e) {
      watchdog_exception('dyniva_wechat_notify', $e);
      return false;
    }
    
    return true;
  }
}
