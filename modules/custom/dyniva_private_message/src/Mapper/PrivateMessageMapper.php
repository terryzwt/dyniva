<?php

namespace Drupal\dyniva_private_message\Mapper;

use Drupal\private_message\Mapper\PrivateMessageMapper as SuperPrivateMessageMapper;

/**
 * Interface for the Private Message Mapper class.
 */
class PrivateMessageMapper extends SuperPrivateMessageMapper {
  
  /**
   * {@inheritdoc}
   */
  public function getUnreadThreadCount($uid, $lastCheckTimestamp) {
    return $this->database->query(
      'SELECT COUNT(thread.id) FROM {private_message_threads} AS thread JOIN ' .
      '{private_message_thread__members} AS member ' .
      'ON member.entity_id = thread.id AND member.members_target_id = :uid ' .
      'WHERE thread.updated > :timestamp AND :uid NOT IN (
        SELECT owner FROM private_messages WHERE id IN (SELECT MAX(private_messages_target_id) FROM private_message_thread__private_messages WHERE entity_id=thread.id GROUP BY entity_id)
      )',
      [
        ':uid' => $uid,
        ':timestamp' => $lastCheckTimestamp,
      ]
    )->fetchField();
  }

  /**
   * {@inheritdoc}
   */
  public function getUnreadThreadIds($uid, $lastCheckTimestamp) {
    return $this->database->query(
      'SELECT thread.id FROM {private_message_threads} AS thread JOIN ' .
      '{private_message_thread__members} AS member ' .
      'ON member.entity_id = thread.id AND member.members_target_id = :uid ' .
      'WHERE thread.updated > :timestamp AND :uid NOT IN (
        SELECT owner FROM private_messages WHERE id IN (SELECT MAX(private_messages_target_id) FROM private_message_thread__private_messages WHERE entity_id=thread.id GROUP BY entity_id)
      )',
      [
        ':uid' => $uid,
        ':timestamp' => $lastCheckTimestamp,
      ]
    )->fetchCol();
  }

  /**
   * 获取消息已读数
   * @param $thread_id Thread ID
   * @param $messageCreateTime 消息创建时间
   */
  public function getReadedCount($thread_id, $messageOwner, $messageCreateTime) {
    return $this->database->query(
      'SELECT COUNT(DISTINCT access_time.owner) FROM pm_thread_access_time access_time '.
      'JOIN private_message_thread__last_access_time rel_access_time ON rel_access_time.last_access_time_target_id = access_time.id '.
      "WHERE access_time.access_time > :messageCreateTime AND rel_access_time.bundle='private_message_thread' AND rel_access_time.entity_id = :thread_id AND access_time.owner <> :messageOwner",
      [
        ':thread_id' => $thread_id,
        ':messageOwner' => $messageOwner,
        ':messageCreateTime' => $messageCreateTime+2,
      ]
    )->fetchField();
  }

}
