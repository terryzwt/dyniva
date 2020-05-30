<?php

namespace Drupal\dyniva_editor_formatter\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Url;
use Drupal\file\Entity\File;
use Drupal\file\Plugin\Field\FieldFormatter\FileFormatterBase;

/**
 * Plugin implementation of the 'videojs_player_list' formatter.
 *
 * @FieldFormatter(
 *   id = "dyniva_videojs_player_list",
 *   label = @Translation("Video.js Player"),
 *   field_types = {
 *     "file",
 *     "video"
 *   }
 * )
 */
class VideoJsPlayerListFormatter extends VideoJsPlayerFormatter implements ContainerFactoryPluginInterface {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    $files = $this->getEntitiesToView($items, $langcode);

    // Early opt-out if the field is empty.
    if (empty($files)) {
      return $elements;
    }

    $image_url = '';
    $entity = $items->getEntity();
    if ($entity && $entity->hasField('image') && $entity->image->target_id) {
      $file = File::load($entity->image->target_id);
      $image_url = file_create_url($file->getFileUri());
    }

    // Collect cache tags to be added for each item in the field.
    $video_items = [];
    foreach ($files as $delta => $file) {
      $video_uri = $file->getFileUri();
      $video_items[] = Url::fromUri(file_create_url($video_uri));
    }
    $elements[] = [
      '#theme' => 'videojs',
      '#items' => $video_items,
      '#player_attributes' => $this->getSettings(),
      '#attached' => [
        'library' => ['dyniva_editor_formatter/videojs'],
      ],
      '#thumbnail' => $image_url,
    ];
    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public static function isApplicable(FieldDefinitionInterface $field_definition) {
    return TRUE;
  }

}
