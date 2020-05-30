<?php

namespace Drupal\dyniva_content_access;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Provides the Replicator manager.
 */
class AccessFilterManager implements AccessFilterInterface {

  /**
   * The services available to perform access filter.
   *
   * @var AccessFilterInterface[]
   */
  protected $filters = [];



  /**
   * {@inheritdoc}
   */
  public function applies($filter_type, EntityInterface $entity, AccountInterface $account) {
    return TRUE;
  }

  /**
   * Adds services.
   *
   * @param AccessFilterInterface $filter
   *   The service to make available for performing access filter.
   */
  public function addFilter(AccessFilterInterface $filter) {
    $this->filters[] = $filter;
  }
  /**
   * Get filters list.
   * 
   * @return \Drupal\dyniva_content_access\AccessFilterInterface[]
   */
  public function getFilter($filter_type) {
    foreach ($this->filters as $filter) {
      if ($filter->support($filter_type)) {
        return $filter;
      }
    }
    return false;
  }

  /**
   * {@inheritdoc}
   */
  public function access($filter_type, EntityInterface $entity, AccountInterface $account) {
    foreach ($this->filters as $filter) {
      if ($filter->applies($filter_type, $entity, $account)) {
        if(!$filter->access($filter_type, $entity, $account)){
          return false;
        }
      }
    }
    return true;
  }
  /**
   * {@inheritdoc}
   */
  public function support($filter_type) {
    return false;
  }


}
