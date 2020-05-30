<?php

namespace Drupal\dyniva_migrate\Plugin\migrate\process;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * Get current user id.
 *
 * @MigrateProcessPlugin(
 *   id = "strtolower"
 * )
  property_type:
    -
      plugin: skip_on_empty
      method: process
      source: 'Property Type'
    -
      plugin: strtolower
 *
 * Example usage with minimal configuration:
 * @code
 * process:
 *   property_type:
 *     -
 *       plugin: skip_on_empty
 *       method: process
 *       source: 'Property Type'
 *     -
 *       plugin: strtolower
 */
class Strtolower extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    return strtolower($value);
  }

}
