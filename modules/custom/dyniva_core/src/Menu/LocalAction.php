<?php

namespace Drupal\dyniva_core\Menu;

use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Menu\LocalActionDefault;
use Drupal\Core\Url;

/**
 * Provides a default implementation for local action plugins.
 */
class LocalAction extends LocalActionDefault {

  /**
   * {@inheritdoc}
   */
  public function getOptions(RouteMatchInterface $route_match) {
    $options = parent::getOptions($route_match);
    $url = Url::fromRoute("<current>");
    $query = \Drupal::request()->getQueryString();
    if (!empty($query)) {
      $query = '?' . $query;
    }
    $options['query']['destination'] = $url->toString() . $query;
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    $contexts = parent::getCacheContexts();
    $contexts[] = 'url';
    return $contexts;
  }

}
