<?php

namespace Drupal\dyniva_migrate\Plugin\migrate\process;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * 基于migrate_file，经image_import处理后再转入media entity
 *
 * @MigrateProcessPlugin(
 *   id = "image_to_media",
 *   handle_multiples = TRUE
 * )
 *
 * * Example usage with minimal configuration:
 * @code
 * field_gallery:
 *   -
 *     plugin: concat
 *     source:
 *       - constants/file_source
 *       - 'pictures'
 *   -
 *     plugin: image_import
 *     destination: constants/file_destination
 *     title: '@title'
 *     alt: !title
 *   -
 *     plugin: dmt_image_to_media
 * @endcode
 */
class ImageToMedia extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    // $value from ImageImport
    if(is_array($value) && isset($value['target_id'])) {
      $value = $value['target_id'];
    }
    if(is_numeric($value)) {
      return $this->saveMedia($value);
    }
    if(is_array($value)) {
      $result = [];
      foreach($value as $id) {
        if(is_array($id) && isset($id['target_id'])) {
          $id = $id['target_id'];
          $result []= $this->saveMedia($id);
        }
      }
      return $result;
    }
  }

  /**
   * Save media.
   */
  public function saveMedia($fileId) {
    $storage = \Drupal::entityTypeManager()->getStorage('media');
    $medias = $storage->loadByProperties(['image' => $fileId]);
    if (!empty($medias)) {
      return reset($medias);
    }
    $file = \Drupal::entityTypeManager()->getStorage('file')->load($fileId);
    $values = [
      'bundle' => 'image',
      'field_media_in_library' => 1,
      'name' => $file->getFilename(),
      'image' => $file,
    ];
    $media = $storage->create($values);
    $media->save();
    return $media;
  }

}
