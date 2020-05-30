<?php

namespace Drupal\dyniva_editor_panelizer\Entity;

use Drupal\Core\Entity\EntityInterface;

/**
 * {@inheritdoc}
 */
interface BlockTypeAttributeEntityInterface extends EntityInterface {

  /**
   * Get all image's url. If entity hasn't set image.
   *
   * It will using default image.
   *
   * Default image: BASE_THEME . '/images/widget-sample/'.
   *
   * @return array
   *   return image url array
   */
  public function getImageUrls();

}
