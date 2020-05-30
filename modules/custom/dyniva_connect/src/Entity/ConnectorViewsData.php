<?php

namespace Drupal\dyniva_connect\Entity;

use Drupal\views\EntityViewsData;
use Drupal\views\EntityViewsDataInterface;

/**
 * Provides Views data for Connector entities.
 */
class ConnectorViewsData extends EntityViewsData implements EntityViewsDataInterface {
  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();

    $data['dyniva_connector']['table']['base'] = array(
      'field' => 'id',
      'title' => $this->t('Connector'),
      'help' => $this->t('The Connector ID.'),
    );

    return $data;
  }

}
