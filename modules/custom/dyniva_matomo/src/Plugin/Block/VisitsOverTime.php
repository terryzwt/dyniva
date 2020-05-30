<?php

namespace Drupal\dyniva_matomo\Plugin\Block;

/**
 * Matomo Real time visitor.
 *
 * @Block(
 *  id = "dyniva_matomo_visit_over_time",
 *  admin_label = @Translation("Matomo Visit over time"),
 * )
 */
class VisitsOverTime extends MatomoWidgetBase {
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
    return 'dyniva_matomo_visit_over_time_api_callback';
  }
  /**
   * {@inheritDoc}
   */
  public function getApiParams() {
    return [
      '_over_time' => 1,
    ];
  }
  /**
   * {@inheritDoc}
   */
  public function getApiMethod() {
    return 'VisitsSummary.get';
  }
}
