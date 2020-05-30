<?php


namespace Drupal\dyniva_matomo\Plugin\Block;


use Drupal\Core\Block\BlockBase;
use Drupal\Core\Url;

/**
 * 今日流量
 *
 * @Block(
 *  id = "dyniva_matomo_site_today",
 *  admin_label = "今日流量"
 * )
 */
class SiteToday extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $site = \Drupal::routeMatch()->getParameter('managed_entity_id');
    if(!($site instanceof \Drupal\node\NodeInterface)) return [];
    $build = [];
    $build['#attached']['library'][] = 'dyniva_matomo/toolbar';
    $settings = [
      'params' => [
        'dyniva-matomo-analytics-toolbar' => [
          'period' => 'day',
          'segment' => '',
        ]
      ],
      'api' => Url::fromRoute('dyniva_matomo.matomo_api')->toString(),
    ];
    $build['#attached']['drupalSettings']['dyniva_matomo'] = $settings;

    $rows = [];
    $run = [];

    $id = $site->id();
    $rows []= [
      "<div>今天</div><div>昨天</div>",
      "<div data-action=\"nb_actions:today_{$id}\">-</div><div data-action=\"nb_actions:yesterday_{$id}\">-</div>",
      "<div data-action=\"nb_visits:today_{$id}\">-</div><div data-action=\"nb_visits:yesterday_{$id}\">-</div>"
    ];
    $run []= "today_$id";
    $run []= "yesterday_$id";

    $settings = [
      'auto_refresh' => false,
      'refresh_interval' => 500,
      'api_method' => 'VisitsSummary.get',
      'api_callback' => 'dyniva_matomo_visits_summary_api_callback',
      'params' => [
        'id' => 'today_'.$id,
        'date' => 'today',
        'idSite' => $site->matomo_site_id->value,
      ]
    ];
    $build['#attached']['drupalSettings']['dyniva_matomo']['widgets']['today_'.$id] = $settings;
    $settings = [
      'auto_refresh' => false,
      'refresh_interval' => 500,
      'api_method' => 'VisitsSummary.get',
      'api_callback' => 'dyniva_matomo_visits_summary_api_callback',
      'params' => [
        'id' => 'yesterday_'.$id,
        'date' => 'yesterday',
        'idSite' => $site->matomo_site_id->value,
      ]
    ];
    $build['#attached']['drupalSettings']['dyniva_matomo']['widgets']['yesterday_'.$id] = $settings;

    $build['#attached']['drupalSettings']['dyniva_matomo']['run'] = $run;

    $table = '<table data-striping="1">';
    $table .= '<thead><tr><th></th><th>浏览量(PV)</th><th>访客数(UV)</th></tr></thead>';
    $table .= '<tbody>';
    foreach($rows as $row) {
      $table .= '<tr>';
      foreach($row as $col) {
        $table .= "<td>$col</td>";
      }
      $table .= '</tr>';
    }
    $table .= '</tbody>';
    $table .= '</table>';
    $build['#markup'] = $table;

    return $build;
  }

}
