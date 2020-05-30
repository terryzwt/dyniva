<?php

namespace Drupal\dyniva_message\Plugin\Notifier;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\message_notify\Plugin\Notifier\MessageNotifierBase;
use Drupal\message_notify\Exception\MessageNotifyException;

/**
 * SMS notifier.
 *
 * @Notifier(
 *   id = "dyniva_sms",
 *   title = @Translation("SMS"),
 *   description = @Translation("Send messages via SMS"),
 *   viewModes = {
 *     "message_notify_sms_body"
 *   }
 * )
 */
class Sms extends MessageNotifierBase {
  
  use StringTranslationTrait;
  
  /**
   * {@inheritdoc}
   */
  public function deliver(array $output = []) {
    if(!\Drupal::moduleHandler()->moduleExists('sms')) {
      \Drupal::logger('dyniva_message')->error('sms module not enabled.');
      return false;
    }
    
    $message = strip_tags($output['message_notify_sms_body']);
    
    try {
      /** @var \Drupal\user\UserInterface $account */
      $account = $this->message->getOwner();
      $phone_number = \Drupal::service('sms.phone_number')->getPhoneNumbers($account, false);
      if (empty($phone_number)) {
        \Drupal::logger('dyniva_message')->error('Message cannot be sent using empty phone number. user_id = @id', [
          '@id' => $account->id()
        ]);
        return false;
      }else{
        $phone_number = reset($phone_number);
      }
      $sms_message = \Drupal\sms\Entity\SmsMessage::create()
      ->setDirection(\Drupal\sms\Direction::OUTGOING)
      ->setMessage($message)
      ->setSenderEntity($account)
      ->addRecipient($phone_number);
      \Drupal::service('sms.provider')->queue($sms_message);
    }
    catch (\Exception $e) {
      watchdog_exception('dyniva_message', $e);
      return false;
    }
    
    return true;
  }
}
