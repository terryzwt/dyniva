<?php

namespace Drupal\dyniva_matomo\Plugin\Block;

use Drupal\Core\Form\FormStateInterface;

/**
 * Matomo entry pages rank.
 *
 * @Block(
 *  id = "dyniva_matomo_events_category",
 *  admin_label = @Translation("Matomo events category"),
 * )
 */
class EventsCategory extends MatomoWidgetBase {
  /**
   * {@inheritDoc}
   */
  public function getContent() {
    return [
      '#markup' => '<div class="chart-wrapper"></div>',
      '#attached' => [
        'library' => ['dyniva_admin/echarts']
      ]
    ];
  }
  /**
   * {@inheritDoc}
   */
  public function getApiCallback() {
    return 'dyniva_matomo_events_category_api_callback';
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'segment' => 'eventAction==content.create'
    ];
  }

  /**
   * {@inheritDoc}
   */
  public function getApiParams() {
    return [
      'segment' => $this->configuration['segment'],
      'date' => date('Y-01-01').','.date('Y-m-d'),
      'secondaryDimension' => 'eventName',
      'period' => 'month'
    ];
  }
  /**
   * {@inheritDoc}
   */
  public function getApiMethod() {
    return 'Events.getCategory';
  }
}
