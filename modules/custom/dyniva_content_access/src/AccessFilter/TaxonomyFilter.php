<?php

namespace Drupal\dyniva_content_access\AccessFilter;

use Drupal\Core\Entity\EntityInterface;
use Drupal\dyniva_content_access\AccessFilterBase;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Drupal\Core\Session\AccountProxyInterface;

class TaxonomyFilter extends AccessFilterBase {
  
  protected $vocabulary;
  
  /**
   * {@inheritdoc}
   */
  public function __construct(RequestStack $request_stack, AccountProxyInterface $current_user, string $vocabulary = 'department') {
    $this->request_stack = $request_stack;
    $this->current_user = $current_user;
    $this->vocabulary = $vocabulary;
    $this->init();
  }
  /**
   * {@inheritdoc}
   */
  protected function init(){
    $this->filter_types = [
      $this->vocabulary
    ];
  }
  /**
   * 
   * @return string
   */
  public function getVocabulary(){
    return $this->vocabulary;
  }
  /**
   * {@inheritdoc}
   */
  public function access($filter_type, EntityInterface $entity, AccountInterface $account) {
    if($entity && $account && $account->isAuthenticated()){
      $uid = $account->id();
      $user = \Drupal::entityTypeManager()->getStorage('user')->load($uid);
      if($user->hasField($this->vocabulary) && !$user->{$this->vocabulary}->isEmpty()) {
        foreach ($user->{$this->vocabulary} as $item) {
          $conditions = [
            'entity_type' => $entity->getEntityTypeId(),
            'entity_id' => $entity->id(),
            'record_type' => $this->vocabulary,
            'record_id' => $item->target_id
          ];
          $records = \Drupal::entityTypeManager()->getStorage('content_access_record')->loadByProperties($conditions);
          if (!empty($records)) {
            return TRUE;
          }
        }
      }
    }
    return FALSE;
  }
}
