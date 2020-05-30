<?php

namespace Drupal\dyniva_migrate\Plugin\migrate\process;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Row;

/**
 * Save Image as media in html.
 *
 * @MigrateProcessPlugin(
 *   id = "save_image_media"
 * )
 *
 * * Example usage with minimal configuration:
 * @code
 * destination:
 *   plugin: 'entity:node'
 * process:
 *   type:
 *     plugin: default_value
 *     default_value: article
 *   body:
 *     plugin: save_image_media
 *     source: body
 *
 *
 * Example usage with full configuration:
 * @code
 *   body:
 *     plugin: save_image_media
 *     source: body
 *     url_prefix: 'http://www.baidu.com'
 */
class SaveImageMedia extends SaveImage {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    $files = $this->replaceImages($value);
    $this->saveMedia($files);
    return $value;
  }

  /**
   * Save media.
   */
  public function saveMedia($files) {
    $storage = \Drupal::entityTypeManager()->getStorage('media');
    foreach ($files as $file) {
      $exists = $storage->loadByProperties(['field_media_image' => $file->id()]);
      if (!empty($exists)) {
        continue;
      }
      $values = [
        'bundle' => 'image',
        'field_media_in_library' => 1,
        'name' => $file->getFilename(),
        'field_media_image' => $file,
      ];
      $media = $storage->create($values);
      $media->save();
    }
  }

}
