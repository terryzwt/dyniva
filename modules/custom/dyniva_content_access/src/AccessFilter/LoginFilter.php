<?php

namespace Drupal\dyniva_content_access\AccessFilter;

use Drupal\Core\Entity\EntityInterface;
use Drupal\dyniva_content_access\AccessFilterBase;
use Drupal\Core\Session\AccountInterface;

class LoginFilter extends AccessFilterBase {

  protected function init(){
    $this->filter_types = [
      'login',
    ];
  }
  /**
   * {@inheritdoc}
   */
  public function access($filter_type, EntityInterface $entity, AccountInterface $account) {
    if($account) {
      return $account->isAuthenticated();
    }
    return false;
  }

  

}
