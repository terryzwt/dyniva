<?php

namespace Drupal\dyniva_core\Queue;

use Drupal\Core\Database\Connection;
use Drupal\Core\Queue\QueueDatabaseFactory;

/**
 * Defines the key/value store factory for the database backend.
 */
class CcmsQueueDatabaseFactory extends QueueDatabaseFactory {

  /**
   * {@inheritdoc}
   */
  public function get($name) {
    return new CcmsDatabaseQueue($name, $this->connection);
  }

}
