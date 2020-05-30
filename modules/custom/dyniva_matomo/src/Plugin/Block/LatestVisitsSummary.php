<?php


namespace Drupal\dyniva_matomo\Plugin\Block;


use Drupal\Core\Block\BlockBase;
use Drupal\Core\Url;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Form\FormStateInterface;

/**
 * Matomo latest visits summary
 *
 * @Block(
 *  id = "dyniva_matomo_latest_visits_summary",
 *  admin_label = @Translation("Matomo latest visits summary"),
 * )
 */
class LatestVisitsSummary extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = [];
    $build['#attached']['library'][] = 'dyniva_matomo/toolbar';
    $settings = [
      'params' => [
//        'date' => 'today',
        'period' => 'day',
        'segment' => '',
      ],
      'api' => Url::fromRoute('dyniva_matomo.matomo_api')->toString(),
    ];
    $build['#attached']['drupalSettings']['dyniva_matomo'] = $settings;

    $id = 'today:visits_summary';
    $settings = [
      'auto_refresh' => false,
      'refresh_interval' => 500,
      'api_method' => 'VisitsSummary.get',
      'api_callback' => 'dyniva_matomo_visits_summary_api_callback',
      'params' => [
        'id' => $id,
        'date' => 'today'
      ]
    ];
    $build['#attached']['drupalSettings']['dyniva_matomo']['run'][] = $id;
    $build['#attached']['drupalSettings']['dyniva_matomo']['widgets'][$id] = $settings;

    $id = 'yesterday:visits_summary';
    $settings = [
      'auto_refresh' => false,
      'refresh_interval' => 500,
      'api_method' => 'VisitsSummary.get',
      'api_callback' => 'dyniva_matomo_visits_summary_api_callback',
      'params' => [
        'id' => $id,
        'date' => 'yesterday'
      ]
    ];
    $build['#attached']['drupalSettings']['dyniva_matomo']['run'][] = $id;
    $build['#attached']['drupalSettings']['dyniva_matomo']['widgets'][$id] = $settings;

    $id = '7:visits_summary';
    $settings = [
      'auto_refresh' => false,
      'refresh_interval' => 500,
      'api_method' => 'Live.getCounters',
      'api_callback' => 'dyniva_matomo_visits_summary_api_callback',
      'params' => [
        'id' => $id,
        'lastMinutes' => 7 * 24 * 60
      ]
    ];
    $build['#attached']['drupalSettings']['dyniva_matomo']['run'][] = $id;
    $build['#attached']['drupalSettings']['dyniva_matomo']['widgets'][$id] = $settings;

    $id = '30:visits_summary';
    $settings = [
      'auto_refresh' => false,
      'refresh_interval' => 500,
      'api_method' => 'Live.getCounters',
      'api_callback' => 'dyniva_matomo_visits_summary_api_callback',
      'params' => [
        'id' => $id,
        'lastMinutes' => 30 * 24 * 60
      ]
    ];
    $build['#attached']['drupalSettings']['dyniva_matomo']['run'][] = $id;
    $build['#attached']['drupalSettings']['dyniva_matomo']['widgets'][$id] = $settings;

    $build['#theme'] = 'dyniva_matomo_latest_visits_summary';

    return $build;
  }

}
