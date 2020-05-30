<?php

namespace Drupal\dyniva_content_access;

use Drupal\Core\Entity\EntityInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Session\AccountInterface;

abstract class AccessFilterBase implements AccessFilterInterface {

  /**
   * @var RequestStack
   */
  protected $request_stack;

  /**
   * @var  AccountProxyInterface
   */
  protected $current_user;
  /**
   * @var  []
   */
  protected $filter_types = [];

  /**
   * 
   * @param RequestStack $request
   * @param AccountProxyInterface $current_user
   */
  public function __construct(RequestStack $request_stack, AccountProxyInterface $current_user) {
    $this->request_stack = $request_stack;
    $this->current_user = $current_user;
    $this->init();
  }
  
  protected function init(){
    $this->filter_types = [];
  }

  /**
   * {@inheritdoc}
   */
  public function support($filter_type) {
    return in_array($filter_type, $this->filter_types);
  }
  /**
   * {@inheritdoc}
   */
  public function applies($filter_type, EntityInterface $entity, AccountInterface $account) {
    return in_array($filter_type, $this->filter_types);
  }
  /**
   * {@inheritdoc}
   */
  public function access($filter_type, EntityInterface $entity, AccountInterface $account) {
    return FALSE;
  }

  

}
