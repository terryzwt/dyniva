<?php

namespace Drupal\dyniva_core\Menu;

use Drupal\menu_trail_by_path\Menu\MenuTreeStorageMenuHelper as MenuTreeStorageMenuHelperBase;

/**
 * Menu tree storage helper.
 */
class MenuTreeStorageMenuHelper extends MenuTreeStorageMenuHelperBase {

  /**
   * {@inheritdoc}
   */
  public function getMenuLinks($menu_name) {
    // Nice to have: implement filtering like
    // public/core/lib/Drupal/Core/Menu/MenuLinkTree.php:153.
    $menu_links   = [];
    $menu_plugins = $this->menuTreeStorage->loadByProperties(['menu_name' => $menu_name, 'enabled' => 1]);
    foreach ($menu_plugins as $plugin_id => $definition) {
      $menu_links[$plugin_id] = $this->menuLinkManager->createInstance($plugin_id);
    }
    return $menu_links;
  }

}
