<?php

/**
 * @file
 * Contains connector.page.inc.
 *
 * Page callback for Connector entities.
 */

use Drupal\Core\Render\Element;
use Drupal\Core\Link;
use Drupal\Core\Url;

/**
 * Prepares variables for Connector templates.
 *
 * Default template: connector.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - elements: An associative array containing the user information and any
 *   - attributes: HTML attributes for the containing element.
 */
function template_preprocess_connector(array &$variables) {
  // Fetch Connector Entity Object.
  $connector = $variables['elements']['#connector'];

  // Helpful $content variable for templates.
  foreach (Element::children($variables['elements']) as $key) {
    $variables['content'][$key] = $variables['elements'][$key];
  }
}
