<?php

namespace Drupal\dyniva_matomo\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Layout\LayoutPluginManagerInterface;
use Drupal\Core\Block\BlockManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Component\Utility\Html;


/**
 * Class AdminController.
 *
 * @package Drupal\dyniva_matomo\Controller
 */
class AdminController extends ControllerBase implements ContainerInjectionInterface {

  /**
   * Layout manager.
   *
   * @var \Drupal\Core\Layout\LayoutPluginManagerInterface
   */
  protected $layoutManager;

  /**
   * Block manager.
   *
   * @var \Drupal\Core\Block\BlockManagerInterface
   */
  protected $blockManager;

  /**
   * AdminController constructor.
   *
   * @param \Drupal\Core\Layout\LayoutPluginManagerInterface $layoutManager
   * @param \Drupal\Core\Block\BlockManagerInterface $blockManager
   */
  public function __construct(LayoutPluginManagerInterface $layoutManager, BlockManagerInterface $blockManager) {
    $this->layoutManager = $layoutManager;
    $this->blockManager = $blockManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.core.layout'),
      $container->get('plugin.manager.block')
    );
  }

  /**
   * Real time page
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *
   * @return array
   */
  public function realTime(Request $request) {
    $realTimeBlock = [
      '#theme' => 'dyniva_matomo_block_real_time',
      '#label' => '实时访问',
      '#content' => [
        'left' => $this->renderBlock('dyniva_matomo_real_time_visitor', [
          'lastMinutes' => 120,
          'auto_refresh' => false,
          'refresh_interval' => 60
        ]),
        'right' => $this->renderBlock('dyniva_matomo_visit_real_time_of_day', [
          'auto_refresh' => false,
          'refresh_interval' => 60
        ])
      ]
    ];
    $regions = [
      'content' => [
        $this->renderBlock('dyniva_matomo_analytics_toolbar', [
          'date_hide' => 1,
          'period_hide' => 1,
          'segment_hide' => 1
        ]),
        $realTimeBlock,
        $this->renderBlock('dyniva_matomo_pages', [
          'label' => '访问明细',
          'auto_refresh' => false,
          'refresh_interval' => 60,
          'filter_limit' => 50,
          'period' => 'year'
        ])
      ]
    ];
    return $this->renderLayout('layout_onecol', $regions);
  }

  public function sites(Request $request) {
    $regions = [
      'content' => $this->renderBlock('dyniva_matomo_sites_today', [
          'auto_refresh' => false,
          'refresh_interval' => 60,
        ])
    ];
    return $this->renderLayout('layout_onecol', $regions);
  }

  public function visits(Request $request) {
    $regions = [
      'content' => [
        $this->renderBlock('dyniva_matomo_analytics_toolbar', [
          'period_hide' => 1,
          'segment_hide' => 1,
          'date_hide' => 1
        ]),
        $this->renderBlock('dyniva_matomo_latest_visits_summary', [
          'label' => '指标概览',
          'auto_refresh' => false,
          'refresh_interval' => 60,
        ]),
        $this->renderBlock('dyniva_matomo_pages', [
          'period' => 'year',
          'auto_refresh' => false,
          'refresh_interval' => 60,
          'filter_limit' => 50
        ])
      ]
    ];
    return $this->renderLayout('layout_onecol', $regions);
  }

  /**
   * 排名
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *
   * @return array
   */
  public function rank(Request $request) {
    $regions = [
      'content' => [
        $this->renderBlock('dyniva_matomo_analytics_toolbar', [
          'date_hide' => 1,
          'period_hide' => 1,
          'segment_hide' => 1,
          'idSite_all' => 1
        ]),
        $this->renderBlock('dyniva_matomo_custom_date_range', [
          'label' => '市县访问量排行榜Top8',
          'auto_refresh' => false,
          'refresh_interval' => 60,
          'filter_limit' => 8,
          'api_callback' => 'dyniva_matomo_city_report_api_callback',
          'segment' => 'eventAction==city.content.view',
          'api_method' => 'Events.getName',
        ]),
        $this->renderBlock('dyniva_matomo_custom_date_range', [
          'label' => '内容访问量排行Top10',
          'auto_refresh' => false,
          'refresh_interval' => 60,
          'api_callback' => 'dyniva_matomo_events_list_api_callback',
          'segment' => 'eventAction==city.content.view',
          'api_method' => 'Events.getCategory',
          'filter_limit' => 10
        ])
      ]
    ];
    return $this->renderLayout('layout_onecol', $regions);
  }

  public function contentPublish(Request $request) {
    $regions = [
      'top' => [
        $this->renderBlock('dyniva_matomo_analytics_toolbar', [
          'date_hide' => 1,
          'period_hide' => 1,
          'segment_hide' => 1,
          'idSite_all' => 1
        ]),
        $this->renderBlock('dyniva_matomo_publish_summary', [
          'auto_refresh' => false,
          'refresh_interval' => 60
        ]),
        $this->renderBlock('dyniva_matomo_events_category', [
          'label' => '年度发文统计',
          'auto_refresh' => false,
          'refresh_interval' => 60,
          'segment' => 'eventAction==city.content.create'
        ]),
        $this->renderBlock('dyniva_matomo_custom', [
          'label' => '热门标签',
          'auto_refresh' => false,
          'refresh_interval' => 60,
//          'method' => 'Events.getNameFromActionId',
//          'action' => 'content.tags',
          'date' => '2020-01-01,'.date('Y-m-d'),
          'table_headers' => '名称,热度',
          'api_callback' => 'dyniva_matomo_events_list_api_callback',
          'segment' => 'eventAction==content.tags',
          'api_method' => 'Events.getName',
          'filter_limit' => 50
        ])
      ],
      'first' => [
        $this->renderBlock('dyniva_matomo_search_keywords', [
          'label' => '搜索关键词',
          'auto_refresh' => false,
          'refresh_interval' => 60,
          'filter_limit' => 50
        ])
      ],
      'second' => [
        $this->renderBlock('dyniva_matomo_search_keywords_no_results', [
          'label' => '无结果搜索关键词',
          'auto_refresh' => false,
          'refresh_interval' => 60,
          'filter_limit' => 50
        ])
      ],
      'bottom' => [
        $this->renderBlock('dyniva_matomo_city_content_publish', [
          'label' => '市县年度发文排行榜Top8',
          'auto_refresh' => false,
          'refresh_interval' => 60
        ]),
        $this->renderBlock('dyniva_matomo_publish_month_summary', [
          'label' => '各市县月度发文统计总览',
          'auto_refresh' => false,
          'refresh_interval' => 60
        ])
      ]
    ];
    return $this->renderLayout('layout_twocol', $regions);
  }

  public function usersReport(Request $request) {
    $regions = [
      'content' => [
        $this->renderBlock('dyniva_matomo_analytics_toolbar', [
          'date_range' => 1,
          'period_hide' => 1,
          'segment_hide' => 1,
          'idSite_all' => 1
        ])
      ]
    ];

    $html = '<p class="bg-counter">中共贵州省委组织部网站在此期间统一身份认证用户<span data-action="total-counter">-</span>个</p>';
    $html .= '<p class="category-counter" data-action="role-counter" data-prefix="其中" data-separator=", " data-template="{0}{1}个"></p>';
    $regions['content'][] = $this->renderBlock('dyniva_matomo_custom', [
      'auto_refresh' => false,
      'refresh_interval' => 60,
      'api_callback' => 'dyniva_matomo_users_summary_api_callback',
      'segment' => 'eventAction==user.create',
      'period' => 'day',
      'api_method' => 'Events.getName',
      'content' => [
        '#markup' => $html
      ]
    ]);

    $html = '<p class="bg-counter">发文总数<span data-action="total-content-create">-</span>篇</p>';
    $regions['content'][] = $this->renderBlock('dyniva_matomo_custom', [
      'auto_refresh' => false,
      'refresh_interval' => 60,
      'api_callback' => 'dyniva_matomo_users_summary2_api_callback',
      'segment' => 'eventAction==content.create',
      'period' => 'day',
      'api_method' => 'Events.getCategory',
      'content' => [
        '#markup' => $html
      ]
    ]);
    return $this->renderLayout('layout_onecol', $regions);
  }

  private function renderLayout($layout, $regions, $params = []) {
    return $this->layoutManager
      ->createInstance($layout, $params)
      ->build($regions);
  }

  private function renderBlock($id, $params = []) {
    $content = $this->blockManager
      ->createInstance($id, $params)
      ->build();
    return $this->theme($content, $id, $params);
  }

  private function theme($content, $id, $config = []) {
    $config['#attributes']['id'] = Html::getUniqueId('block-' . $id);
    $config['#attributes']['class'][] = 'block';
    $config['#attributes']['class'][] = Html::cleanCssIdentifier('block-' . $id);
    $label = $config['label'] ?? '';
    $attributes = $config['#attributes'];
    return [
      '#theme' => 'dyniva_matomo_block_renderer',
      '#content' => $content,
      '#attributes' => [
        'id' => $attributes['id'],
        'class' => $attributes['class'],
      ],
      '#label' => $label
    ];
  }

}
