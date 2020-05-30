<?php

namespace Drupal\dyniva_connect\Entity;

use Drupal\views\EntityViewsData;
use Drupal\views\EntityViewsDataInterface;

/**
 * Provides Views data for Connection entities.
 */
class ConnectionViewsData extends EntityViewsData implements EntityViewsDataInterface {
  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();

    $data['dyniva_connection']['table']['base'] = array(
      'field' => 'id',
      'title' => $this->t('Connection'),
      'help' => $this->t('The Connection ID.'),
    );

    return $data;
  }

}
