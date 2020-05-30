<?php

namespace Drupal\dyniva_matomo\Plugin\Block;

use Drupal\dyniva_matomo\Form\AnalyticsToolbarForm;

/**
 * Matomo Real time visitor.
 *
 * @Block(
 *  id = "dyniva_matomo_visit_real_time_of_day",
 *  admin_label = @Translation("Matomo Visit real time of day"),
 * )
 */
class VisitsRealTimeOfDay extends MatomoWidgetBase {
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
    return 'dyniva_matomo_visit_real_time_of_day_api_callback';
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
    return 'VisitTime.getVisitInformationPerServerTime';
  }
}
