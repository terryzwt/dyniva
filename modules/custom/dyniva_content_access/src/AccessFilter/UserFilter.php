<?php

namespace Drupal\dyniva_content_access\AccessFilter;

use Drupal\Core\Entity\EntityInterface;
use Drupal\dyniva_content_access\AccessFilterBase;
use Drupal\Core\Session\AccountInterface;

class UserFilter extends AccessFilterBase {

  protected function init(){
    $this->filter_types = [
      'user',
    ];
  }
  /**
   * {@inheritdoc}
   */
  public function access($filter_type, EntityInterface $entity, AccountInterface $account) {
    if($entity && $account && $account->isAuthenticated()){
      $uid = $account->id();
      $conditions = [
        'entity_type' => $entity->getEntityTypeId(),
        'entity_id' => $entity->id(),
        'record_type' => 'user',
        'record_id' => $uid
      ];
      $records = \Drupal::entityTypeManager()->getStorage('content_access_record')->loadByProperties($conditions);
      if (!empty($records)) {
        return TRUE;
      }
    }
    return FALSE;
  }

  

}
