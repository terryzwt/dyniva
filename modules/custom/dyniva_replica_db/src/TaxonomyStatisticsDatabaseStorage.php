<?php

namespace Drupal\dyniva_replica_db;

use Drupal\Core\Database\Connection;
use Drupal\Core\State\StateInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Drupal\statistics\TaxonomyStatisticsDatabaseStorage as TaxonomyStatisticsDatabaseStorageBase;
use Drupal\statistics\StatisticsViewsResult;

/**
 * Provides the default database storage backend for statistics.
 */
class TaxonomyStatisticsDatabaseStorage extends TaxonomyStatisticsDatabaseStorageBase {

  /**
  * The database connection used.
  *
  * @var \Drupal\Core\Database\Connection
  */
  protected $connection_replica;

  /**
   * Constructs the statistics storage.
   *
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection for the taxonomy view storage.
   * @param \Drupal\Core\State\StateInterface $state
   *   The state service.
   */
  public function __construct(Connection $connection, StateInterface $state, RequestStack $request_stack, Connection $connection_replica) {
    parent::__construct($connection, $state, $request_stack);
    $this->connection_replica = $connection_replica;
  }

  /**
   * {@inheritdoc}
   */
  public function fetchViews($ids) {
    $views = $this->connection_replica
      ->select('taxonomy_counter', 'nc')
      ->fields('nc', ['totalcount', 'daycount', 'timestamp'])
      ->condition('tid', $ids, 'IN')
      ->execute()
      ->fetchAll();
    foreach ($views as $id => $view) {
      $views[$id] = new StatisticsViewsResult($view->totalcount, $view->daycount, $view->timestamp);
    }
    return $views;
  }

  /**
   * {@inheritdoc}
   */
  public function fetchAll($order = 'totalcount', $limit = 5) {
    assert(in_array($order, ['totalcount', 'daycount', 'timestamp']), "Invalid order argument.");

    return $this->connection_replica
      ->select('taxonomy_counter', 'nc')
      ->fields('nc', ['tid'])
      ->orderBy($order, 'DESC')
      ->range(0, $limit)
      ->execute()
      ->fetchCol();
  }

  /**
   * {@inheritdoc}
   */
  public function maxTotalCount() {
    $query = $this->connection_replica->select('taxonomy_counter', 'nc');
    $query->addExpression('MAX(totalcount)');
    $max_total_count = (int)$query->execute()->fetchField();
    return $max_total_count;
  }

}
