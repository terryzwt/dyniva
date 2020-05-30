<?php

namespace Drupal\dyniva_core\Queue;

use Drupal\Core\Database\Connection;
use Drupal\Core\Queue\DatabaseQueue;

/**
 * Default queue implementation.
 *
 * @ingroup queue
 */
class CcmsDatabaseQueue extends DatabaseQueue {

  /**
   * {@inheritdoc}
   */
  public function releaseItem($item) {
    try {
      $update = $this->connection->update(static::TABLE_NAME)
        ->fields([
          'expire' => 0,
          'created' => time(),
        ])
        ->condition('item_id', $item->item_id);
      return $update->execute();
    }
    catch (\Exception $e) {
      $this->catchException($e);
      // If the table doesn't exist we should consider the item released.
      return TRUE;
    }
  }

}
