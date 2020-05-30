<?php

namespace Drupal\dyniva_migrate\Plugin\migrate\process;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Row;
use Drupal\migrate_file\Plugin\migrate\process\ImageImport;

/**
 * 基于migrate_file的多图片导入，由于image_import plugin不支持多图片，所以开发此插件实现之
 * source_path用于定位导入图片的路径，因为concat plugin不支持值分割后对每一块合并字串
 * 除source_path之外的所有属性都与image_import一样
 * 
 * Example:
 *
 * @code
 * process:
 *   field_image:
 *     plugin: images_import
 *     source: image
 *     source_path: constants/source_path
 *     destination: constants/file_destination
 *     uid: @uid
 *     title: title
 *     alt: !file
 *     width: 1920
 *     height: 1080
 *     skip_on_missing_source: true
 * @endcode
 *
 * @see \Drupal\migrate\Plugin\MigrateProcessInterface
 *
 * @MigrateProcessPlugin(
 *   id = "images_import",
 *   handle_multiples = TRUE
 * )
 */
class ImagesImport extends ImageImport {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {

    if(is_array($value)) {
      $result = [];
      foreach($value as $_value) {
        if($this->configuration['source_path']) {
          $source_path = $this->getPropertyValue($this->configuration['source_path'], $row) ?: 'public://';
          $_value = $source_path.$_value;
        }
        $result []= parent::transform($_value, $migrate_executable, $row, $destination_property);
      }
      return $result;
    } else {
      if($this->configuration['source_path']) {
        $source_path = $this->getPropertyValue($this->configuration['source_path'], $row) ?: 'public://';
        $value = $source_path.$value;
      }
      return parent::transform($value, $migrate_executable, $row, $destination_property);
    }
  }

}
