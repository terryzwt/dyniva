<?php

namespace Drupal\dyniva_content_access;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines an interface for AccessFilter plugins.
 */
interface AccessFilterInterface {

  /**
   * 
   * @param string $filter_type
   * 
   * @return boolean
   */
  public function support($filter_type);
  /**
   * 
   * @param string $filter_type
   * @param EntityInterface $entity
   * 
   * @return boolean
   */
  public function applies($filter_type, EntityInterface $entity, AccountInterface $account);
  /**
   * 
   * @param string $filter_type
   * @param EntityInterface $entity
   * 
   * @return boolean
   */
  public function access($filter_type, EntityInterface $entity, AccountInterface $account);

}
