<?php

namespace Drupal\dyniva_matomo\Plugin\Block;

/**
 * Matomo Real time visitor.
 *
 * @Block(
 *  id = "dyniva_matomo_browsers",
 *  admin_label = @Translation("Matomo Browsers"),
 * )
 */
class Browsers extends MatomoWidgetBase {
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
    return 'dyniva_matomo_browsers_api_callback';
  }
  /**
   * {@inheritDoc}
   */
  public function getApiParams() {
    return [];
  }
  /**
   * {@inheritDoc}
   */
  public function getApiMethod() {
    return 'DevicesDetection.getBrowsers';
  }
}
