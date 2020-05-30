<?php

/**
 * @file
 * Contains connection.page.inc.
 *
 * Page callback for Connection entities.
 */

use Drupal\Core\Render\Element;
use Drupal\Core\Link;
use Drupal\Core\Url;

/**
 * Prepares variables for Connection templates.
 *
 * Default template: connection.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - elements: An associative array containing the user information and any
 *   - attributes: HTML attributes for the containing element.
 */
function template_preprocess_connection(array &$variables) {
  // Fetch Connection Entity Object.
  $connection = $variables['elements']['#connection'];

  // Helpful $content variable for templates.
  foreach (Element::children($variables['elements']) as $key) {
    $variables['content'][$key] = $variables['elements'][$key];
  }
}
