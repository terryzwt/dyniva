<?php

namespace Drupal\dyniva_editor_panelizer\Entity;

use Drupal\eck\Entity\EckEntity;

/**
 * {@inheritdoc}
 */
class BlockTypeAttributeEntity extends EckEntity implements BlockTypeAttributeEntityInterface {

  /**
   * Get image url array.
   */
  public function getImageUrls() {
    $urls = [];

    if (!$this->get('images')->isEmpty()) {
      foreach ($this->images as $image) {
        if ($image->entity) {
          $urls[] = file_create_url($image->entity->getFileUri());
        }
      }
    }
    else {
      // 如果图片不存在就从基主题里面去获取默认图片.
      $image_name = 'widget_' . $this->block_type->target_id . '.png';

      $theme = \Drupal::service('theme_handler')->getDefault();
      // 获取base theme.
      $base_theme = \Drupal::service('theme_handler')->listInfo()[$theme]->base_theme;
      $path = drupal_get_path('theme', $base_theme) . '/images/widget-sample/' . $image_name;

      if (file_exists($path)) {
        $urls[] = file_create_url($path);
      }
    }

    return $urls;
  }

}
