<?php

namespace Drupal\dyniva_migrate\Plugin\migrate\process;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * Excel date to string/timestamp.
 *
 * @MigrateProcessPlugin(
 *   id = "address_administrative_area"
 * )
 *
 * Example usage with minimal configuration:
 * @code
 * process:
 *   date:
 *     plugin: address_administrative_area
 *     country_code: AU
 */
class AddressAdministrativeArea extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    $country_code = $this->configuration['country_code'] ?? 'CN';
    $subdivisions = \Drupal::service('address.subdivision_repository')
      ->getList([$country_code], 'en');
    $value = trim($value);
    foreach($subdivisions as $key => $subdivision) {
      if($this->contains($subdivision, $value)) {
        $value = $key;
      }
    }
    return $value;
  }

  /**
   * Is contains.
   *
   * @param $haystack
   * @param $needle
   * @return bool
   */
  function contains($haystack, $needle) {
    return strpos($haystack, $needle) !== false;
  }

}
