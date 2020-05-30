<?php

namespace Drupal\dyniva_connect;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Routing\LinkGeneratorTrait;
use Drupal\Core\Url;

/**
 * Defines a class to build a listing of Connector entities.
 *
 * @ingroup dyniva_connect
 */
class ConnectorListBuilder extends EntityListBuilder {
  use LinkGeneratorTrait;
  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['id'] = $this->t('Connector ID');
    $header['url'] = $this->t('Connector Url');
    $header['name'] = $this->t('Name');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /* @var $entity \Drupal\dyniva_connect\Entity\Connector */
    $row['id'] = $entity->id();
    $row['url'] = [
      'data' => [
        '#theme' => 'item_list',
        '#items' => [
          ['#markup' => Url::fromRoute('dyniva_connect.message',['connector' => $entity->id()],['absolute' => true])->toString()],
          ['#markup' => Url::fromRoute('dyniva_connect.connect',['connector' => $entity->id()],['absolute' => true])->toString()],
        ]
      ]
    ];
    $row['name'] = $this->l(
      $entity->label(),
      new Url(
        'entity.connector.edit_form', array(
          'connector' => $entity->id(),
        )
      )
    );
    return $row + parent::buildRow($entity);
  }

}
