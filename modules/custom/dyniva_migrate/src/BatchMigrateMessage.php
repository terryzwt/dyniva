<?php

namespace Drupal\dyniva_migrate;

use Drupal\migrate\MigrateMessageInterface;

/**
 * Migrate message.
 */
class BatchMigrateMessage implements MigrateMessageInterface {

  /**
   * Message array.
   *
   * @var array
   */
  protected $messages = [];

  /**
   * Output a message from the migration.
   *
   * @param string $message
   *   The message to display.
   * @param string $type
   *   The type of message to display.
   *
   * @see drush_log()
   */
  public function display($message, $type = 'status') {
    $messages[] = $message;
  }

  /**
   * Get message array.
   */
  public function getMessage() {
    return $this->messages;
  }

}
