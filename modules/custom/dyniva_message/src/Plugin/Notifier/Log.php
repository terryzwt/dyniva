<?php

namespace Drupal\dyniva_message\Plugin\Notifier;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\message_notify\Plugin\Notifier\MessageNotifierBase;

/**
 * Log notifier.
 *
 * @Notifier(
 *   id = "dyniva_log",
 *   title = @Translation("System Log"),
 *   description = @Translation("Send messages via System Log"),
 *   viewModes = {
 *     "default"
 *   }
 * )
 */
class Log extends MessageNotifierBase {
  
  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function deliver(array $output = []) {

    $message = trim(strip_tags($output['default']));
    /** @var \Drupal\user\UserInterface $account */
    $account = $this->message->getOwner();
    \Drupal::logger('dyniva_message')->notice("user: @var1, message: @var2", [
      '@var1' => $account->getDisplayName(),
      '@var2' => $message
    ]);

    return true;
  }

}
