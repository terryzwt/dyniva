<?php

namespace Drupal\dyniva_migrate\Plugin\migrate\process;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * Get current user id.
 *
 * @MigrateProcessPlugin(
 *   id = "current_user_id"
 * )
 *
 * Example usage with minimal configuration:
 * @code
 * process:
 *   uid:
 *     plugin: current_user_id
 */
class CurrentUserId extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    return \Drupal::currentUser()->id();
  }

}
