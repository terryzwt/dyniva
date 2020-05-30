<?php

namespace Drupal\dyniva_core\Site;

use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Site\MaintenanceMode as CoreMaintenanceMode;

/**
 * Provides the default implementation of the maintenance mode service.
 */
class MaintenanceMode extends CoreMaintenanceMode {

  /**
   * {@inheritdoc}
   */
  public function applies(RouteMatchInterface $route_match) {
    if (!$this->state->get('system.maintenance_mode')) {
      return FALSE;
    }

    if ($route = $route_match->getRouteObject()) {
      if ($route->getOption('_maintenance_access')) {
        return FALSE;
      }
      if ($this->state->get('dyniva_core.maintenance_mode')) {
        $path = $route->getPath();
        if (!preg_match('#^/manage#', $path)) {
          return FALSE;
        }
      }
    }

    return TRUE;
  }

}
