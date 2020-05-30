<?php

namespace Drupal\dyniva_migrate\Plugin\migrate\process;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;
use Drupal\Component\Utility\UrlHelper;

/**
 * Save Image as file in html.
 *
 * @MigrateProcessPlugin(
 *   id = "save_image"
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
 *     plugin: save_image
 *     source: body
 *
 * * Example usage with full configuration:
 * @code
 *   body:
 *     plugin: save_image
 *     source: body
 *     url_prefix: 'http://www.baidu.com'
 */
class SaveImage extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    $this->replaceImages($value);
    return $value;
  }

  /**
   * Replace image.
   *
   * @param string $value
   *   Content string.
   *
   * @return \Drupal\file\FileInterface[]|false[]|\Drupal\Core\Entity\EntityInterface[]
   *   Files array.
   */
  public function replaceImages(&$value) {
    $files = [];
    preg_match_all('/<img[^>]* src=(["\']{1})(.*?)(\1)(.*?)>/i', $value, $matchs);

    foreach ($matchs[0] as $index => $item) {
      $src = urldecode($matchs[2][$index]);
      $file_uri = parse_url($src)['path'];
      $prefix = "";
      if (!empty($this->configuration['url_prefix'])) {
        $prefix = $this->configuration['url_prefix'];
      }
      if (!UrlHelper::isExternal($src)) {
        $src = rtrim($prefix, '/') . '/' . ltrim($src, '/');
      }
      $file_uri = file_default_scheme() . '://import_images/' . ltrim($file_uri, '/');

      $files = \Drupal::entityTypeManager()->getStorage('file')->loadByProperties(['uri' => $file_uri]);
      $file = FALSE;
      if (!empty($files)) {
        $file = reset($files);
      }
      elseif (UrlHelper::isExternal($src)) {
        $dir_path = drupal_dirname($file_uri);
        file_prepare_directory($dir_path, FILE_CREATE_DIRECTORY);
        $content = file_get_contents($src);
        $file = file_save_data($content, $file_uri);
      }
      if ($file) {
        $files[] = $file;
        $file_url = file_create_url($file->getFileUri());
        $file_url = file_url_transform_relative($file_url);
        $replace = "<img data-entity-type=\"file\" data-entity-uuid=\"{$file->uuid()}\" src=\"{$file_url}\" >";
        $value = str_replace($item, $replace, $value);
      }
    }
    return $files;
  }

}
