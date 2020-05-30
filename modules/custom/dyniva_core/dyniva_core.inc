<?php

/**
 * @file
 * Helpler functions.
 */

/**
 * 获取 wl entity info.
 *
 * @return Ambigous <Returns, An>
 */
use Drupal\Core\Url;

/**
 * Get ccms entity info.
 *
 * @return array
 *   Entity info array.
 */
function dyniva_core_get_ccms_entity_info() {
  $cache = &drupal_static(__FUNCTION__, []);

  if (!$cache) {
    $cache = \Drupal::moduleHandler()->invokeAll('ccms_entity_info');
    \Drupal::moduleHandler()->alter('ccms_entity_info', $cache);
  }

  return $cache;
}

/**
 * Generate link with attributes.
 */
function dyniva_core_generate_link($destination, $link_type = 'internal', $attributes = []) {
  $url = Url::fromUri($link_type . ':' . $destination);

  if (!empty($attributes)) {
    $url->setOptions($attributes);
  }
  return $url;
}

/**
 * Generate absolute path by route name.
 */
function dyniva_core_generate_asolute_path($route_name = '<current>') {
  return Url::fromRoute($route_name, [], [
    'absolute' => 'true',
  ])->toString();
}
