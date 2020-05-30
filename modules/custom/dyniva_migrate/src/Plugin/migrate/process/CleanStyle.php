<?php

namespace Drupal\dyniva_migrate\Plugin\migrate\process;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * Clean style in html.
 *
 * @MigrateProcessPlugin(
 *   id = "clean_style"
 * )
 */
class CleanStyle extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    $value = preg_replace('/(<[^>]+) style=([\'"]{1}).*?(\2)/i', '$1', $value);
    $value = preg_replace('/(<[^>]+) class=([\'"]{1}).*?(\2)/i', '$1', $value);
    return $value;
  }

}
