<?php

namespace Drupal\dyniva_migrate;

use Drupal\migrate\Plugin\MigrationInterface;

/**
 * Migrate session row.
 */
class MigrateSessionRow implements \Iterator, \Countable {

  /**
   * Current index.
   *
   * @var int
   */
  protected $index = 1;

  /**
   * Migration instence.
   *
   * @var \Drupal\migrate\Plugin\MigrationInterface
   */
  protected $migration = NULL;

  /**
   * Temp store.
   *
   * @var \Drupal\user\PrivateTempStore
   */
  protected $tempStore = NULL;

  /**
   * {@inheritdoc}
   */
  public function __construct(MigrationInterface $migration) {
    $this->migration = $migration;
    /*
     * @var \Drupal\user\PrivateTempStoreFactory $privateStore
     */
    $privateStore = \Drupal::service('user.private_tempstore');
    $this->tempStore = $privateStore->get('dyniva_migrate');
  }

  /**
   * {@inheritdoc}
   */
  public function current() {
    $key = $this->getCacheKey();
    return $this->tempStore->get($key);
  }

  /**
   * {@inheritdoc}
   */
  public function key() {
    return $this->index;
  }

  /**
   * {@inheritdoc}
   */
  public function next() {
    $this->index++;
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function rewind() {
    $this->index = 1;
  }

  /**
   * {@inheritdoc}
   */
  public function valid() {
    return $this->index == 1;
  }

  /**
   * {@inheritdoc}
   */
  public function count() {
    $key = $this->getCacheKey();
    return \Drupal::state()->get($key, 0);
  }

  /**
   * Get cache key.
   */
  public function getCacheKey() {
    return 'dyniva_migrate_' . $this->migration->id();
  }

  /**
   * Set total count.
   */
  public function setCount($total) {
    $key = $this->getCacheKey();
    \Drupal::state()->set($key, $total);
  }

  /**
   * Set current row data.
   */
  public function setCurrent($current) {
    $key = $this->getCacheKey();
    $this->tempStore->set($key, $current);
  }

}
