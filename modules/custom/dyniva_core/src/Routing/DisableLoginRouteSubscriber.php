<?php

namespace Drupal\dyniva_core\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Disable login page.
 */
class DisableLoginRouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    $config = \Drupal::configFactory()->getEditable('dyniva_core.site_info_config');
    if (!empty($config->get('disable_login')) && $route = $collection->get('user.login')) {
      $route->setRequirement('_access', 'FALSE');
    }
  }

}
