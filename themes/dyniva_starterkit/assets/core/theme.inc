<?php
/**
 * Implements HOOK_theme().
 * If Enabled theme rebuild & administer logined Show noties message
 */

/* Menus Active */
if(!function_exists('_wencore_menu_active_item')){
  function _wencore_menu_active_item(&$items) {
    $current_path = \Drupal::request()->getRequestUri();
    foreach ($items as $key => $item) {
      // if path is current path, set active to li
      if ($item['url']->toString() == $current_path) {
        // add active linl
        $items[$key]['is_active'] = true;
        $items[$key]['attributes']['class'] = 'active-item';
      }
      if (!empty($items[$key]['below'])) {
        _wencore_menu_active_item($items[$key]['below']);
      }
    }
  }
}


/* Auto Rebuilding */
if(!function_exists('_wencore_theme')){
  function _wencore_theme(&$existing, $type, $theme, $path) {
    // If we are auto-rebuilding the theme registry, warn about the feature.
    $cur_theme = \Drupal::theme()->getActiveTheme()->getName();
    $flood_name = $cur_theme . '.rebuild_registry_warning';
    $is_admin_route = \Drupal::service('router.admin_context')->isAdminRoute(\Drupal::routeMatch()->getRouteObject());
    if (
      // Don't display on update.php or install.php.
      !defined('MAINTENANCE_MODE')
      // Only display for theme admins.
      && \Drupal::currentUser()->hasPermission('administer themes')
      && theme_get_setting('custom_rebuild_registry')
      // Always display in the admin section, otherwise limit to three per hour.
      && ($is_admin_route || \Drupal::flood()->isAllowed($flood_name, 3))
    ) {
      \Drupal::flood()->register($flood_name);
      drupal_set_message(t('For easier theme development, the theme registry is being rebuilt on every page request. It is <em>extremely</em> important to <a href="!link">turn off this feature</a> on production websites.', array('!link' => url('admin/appearance/settings/' . $$cur_theme))), 'warning', FALSE);
    }

    // hook_theme() expects an array, so return an empty one.
    return array();
  }
}



/* Empty Region */
if(!function_exists('_wencore_regionEmpty')){
  function _wencore_regionEmpty(&$variables) {
    $sidebar_first = !empty(trim(strip_tags(render(
      $variables['page']['sidebar_first']
    ))));
    $sidebar_second = !empty(trim(strip_tags(render(
      $variables['page']['sidebar_second']
    ))));

    if (!$sidebar_first){
      $variables['page']['sidebar_first'] = NULL;
    }
    if (!$sidebar_second){
      $variables['page']['sidebar_second'] = NULL;
    }
  }
}

