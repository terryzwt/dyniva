<?php

namespace Drupal\dyniva_migrate\Plugin\migrate\process;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;
use Drupal\migrate\MigrateSkipRowException;
use Drupal\media\MediaInterface;

/**
 * 从图库提取图片.
 *
 * @MigrateProcessPlugin(
 *   id = "media_library",
 *   handle_multiples = TRUE
 * )
 *
 * Example usage with minimal configuration:
 * @code
 * process:
 *   photo:
 *     plugin: media_library
 *     bundle: image
 *     dest_type: media # or file
 * @endcode
 */
class MediaLibrary extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    $bundle = $this->configuration['bundle'] ?? 'image';
    $dest_type = $this->configuration['dest_type'] ?? 'media';
    if(is_array($value)) {
      $results = [];
      foreach($value as $_value) {
        if($dest_type == 'file') {
          $source_field = self::getSourceField($bundle);
          $results []= $this->getMedia($_value, $bundle)->get($source_field)->entity;
        } else {
          $results []= $this->getMedia($_value, $bundle);
        }
      }
      return $results;
    } else {
      if($dest_type == 'file') {
        $source_field = self::getSourceField($bundle);
        return $this->getMedia($value, $bundle)->get($source_field)->entity;
      } else {
        return $this->getMedia($value, $bundle);
      }
    }
  }

  /**
   * @param $value
   * @param $bundle
   * @return \Drupal\Core\Entity\EntityInterface[]
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  private function getMedias($value, $bundle) {
    $files = \Drupal::entityTypeManager()->getStorage('file')->loadByProperties([
      'filename' => $value
    ]);
    $ids = [];
    foreach($files as $file) {
      $ids []= $file->id();
    }
    if($ids) {
      $source_field = self::getSourceField($bundle);
      return \Drupal::entityTypeManager()->getStorage('media')->loadByProperties([
        'bundle' => $bundle,
        'uid' => \Drupal::currentUser()->id(),
        $source_field => $ids
      ]);
    }
    return [];
  }

  /**
   * @param $value
   * @param $bundle
   * @return \Drupal\Core\Entity\EntityInterface
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\migrate\MigrateSkipRowException
   */
  private function getMedia($value, $bundle) {
    $images = $this->getMedias($value, $bundle);
    if($images) {
      return reset($images);
    }
    throw new MigrateSkipRowException("Source file $value does not exist.");
  }

  /**
   * @param $bundle
   * @return string
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public static function getSourceField($bundle) {
    $type = \Drupal::entityTypeManager()->getStorage('media_type')->load($bundle);
    $configuration = $type->getSource()->getConfiguration();
    return $configuration['source_field'] ?? 'image';
  }

}
