<?php

namespace Drupal\dyniva_private_message\Entity;

use Drupal\private_message\Entity\PrivateMessageThread as SuperPrivateMessageThread;

class PrivateMessageThread extends SuperPrivateMessageThread {

  protected $disableClearCache;

  /**
   * {@inheritdoc}
   */
  public function save($clearCache = true) {
    if(!$clearCache) {
      $this->disableClearCache = true;
    }
    parent::save();
    if(!$clearCache) {
      $this->disableClearCache = false;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function isMember($id) {
    $members = $this->get('members');
    foreach ($members as $member) {
      if ($member->target_id == $id) {
        return TRUE;
      }
    }

    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function clearCacheTags() {
    if(isset($this->disableClearCache) && $this->disableClearCache) {
      return;
    }
    parent::clearCacheTags();
  }

  /**
   * {@inheritdoc}
   */
  public function getMembers() {
    $users = [];
    $i = 0;
    foreach($this->get('members') as $member) {
      if($i++ > 20) break;
      $users[]= $member->entity;
    }
    return $users;
  }

}
