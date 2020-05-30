<?php

namespace Drupal\dyniva_private_message\Service;

use Drupal\private_message\Service\PrivateMessageService as SuperPrivateMessageService;

/**
 * The Private Message service for the private message module.
 */
class PrivateMessageService extends SuperPrivateMessageService {

  /**
   * {@inheritdoc}
   */
  public function getUnreadThreadIds() {
    $uid = $this->currentUser->id();
    $last_check_timestamp = $this->userData->get(self::MODULE_KEY, $uid, self::LAST_CHECK_KEY);
    $last_check_timestamp = is_numeric($last_check_timestamp) ? $last_check_timestamp : 0;

    return $this->mapper->getUnreadThreadIds($uid, $last_check_timestamp);
  }

  /**
   * 获取消息已读数
   * @param $thread_id Thread ID
   * @param $messageCreateTime 消息创建时间
   */
  public function getReadedCount($thread_id, $messageOwner, $messageCreateTime) {
    return $this->mapper->getReadedCount($thread_id, $messageOwner, $messageCreateTime);
  }
}
