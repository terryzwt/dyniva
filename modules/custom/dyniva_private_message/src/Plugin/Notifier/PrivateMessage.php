<?php

namespace Drupal\dyniva_private_message\Plugin\Notifier;

use Symfony\Polyfill\Mbstring\Mbstring;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\message_notify\Plugin\Notifier\MessageNotifierBase;
use Drupal\private_message\Entity\PrivateMessageThread;
use Drupal\private_message\Entity\PrivateMessage as PrivateMessageEntity;
use Drupal\Component\Utility\Html;

/**
 * Private message notifier.
 *
 * @Notifier(
 *   id = "private_message",
 *   title = @Translation("Private Message"),
 *   description = @Translation("Send messages via Private Message"),
 *   viewModes = {
 *     "mail_subject",
 *     "mail_body"
 *   }
 * )
 */
class PrivateMessage extends MessageNotifierBase {
  
  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function deliver(array $output = []) {

    $text = $output['mail_body'];
    $subject = trim(strip_tags(Html::decodeEntities($output['mail_subject'])));
    
    $account = $this->message->getOwner();
    $private_message_thread = PrivateMessageThread::create();
//     $private_message_thread->addMember(user_load(1));
    $private_message_thread->addMember($account);
    
    if(Mbstring::mb_strlen($subject) > 50) {
      $subject = Mbstring::mb_substr($subject, 0, 50).'...';
    }
    $private_message_thread->set('field_pm_subject', $subject);
    $message = PrivateMessageEntity::create();
    $message->setOwnerId(1);
    $message->created = time();
    $message->message->value = $text;
    $message->message->format = 'full_html';
    $message->save();

    $private_message_thread->addMessage($message);
    $private_message_thread->type = 'person';
    $private_message_thread->deny_reply = true;
    $private_message_thread->save();

    return true;
  }

}
