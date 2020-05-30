<?php

namespace Drupal\dyniva_matomo\Plugin\Block;

/**
 * Matomo Real time visitor.
 *
 * @Block(
 *  id = "dyniva_matomo_visit_info_per_local_time",
 *  admin_label = @Translation("Matomo Visit info per local time"),
 * )
 */
class VisitInformationPerLocalTime extends MatomoWidgetBase {
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
    return 'dyniva_matomo_visit_info_per_local_time_api_callback';
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
    return 'VisitTime.getVisitInformationPerLocalTime';
  }
}
