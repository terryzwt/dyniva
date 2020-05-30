<?php

namespace Drupal\dyniva_migrate\Plugin\migrate\process;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;
use PhpOffice\PhpSpreadsheet\Shared\Date;

/**
 * Excel date to string/timestamp.
 *
 * @MigrateProcessPlugin(
 *   id = "excel_date"
 * )
 *
 * Example usage with minimal configuration:
 * @code
 * process:
 *   date:
 *     plugin: excel_date
 *     type: string
 * @endcode
 */
class ExcelDate extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    if(is_numeric($value)) {
      $type = $this->configuration['type'] ?? 'string';
      try {
        switch($type) {
          case 'string':
            $date = Date::excelToDateTimeObject($value);
            $value = $date->format('Y-m-d');
            break;
          case 'timestamp':
            $value = Date::excelToTimestamp($value);
            break;
        }
      } catch(\Exception $e) {}
    }
    return $value;
  }

}
