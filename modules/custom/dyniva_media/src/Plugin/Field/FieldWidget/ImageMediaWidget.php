<?php

namespace Drupal\dyniva_media\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Form\FormStateInterface;
use Drupal\image\Plugin\Field\FieldWidget\ImageWidget;

/**
 * @FieldWidget(
 *   id = "image_dyniva",
 *   label = @Translation("Dyniva Image 已废弃"),
 *   field_types = {
 *     "image"
 *   }
 * )
 */
class ImageMediaWidget extends ImageWidget {
}
