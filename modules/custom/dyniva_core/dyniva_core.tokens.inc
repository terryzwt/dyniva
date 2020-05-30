<?php

/**
 * @file
 * Tokens.
 */

use Drupal\Core\Render\BubbleableMetadata;

/**
 * @file
 * Builds placeholder replacement tokens system-wide data.
 *
 * This file handles tokens for the global 'site' and 'date' tokens.
 */

/**
 * Implements hook_token_info().
 */
function dyniva_core_token_info() {

  // Site-wide global tokens.
  $site['base_path'] = [
    'name' => t("Base Path"),
    'description' => t("The base path of the site."),
  ];
  $site['site_path'] = [
    'name' => t("Site Path"),
    'description' => t("The site path of the site."),
  ];

  return [
    'tokens' => [
      'site' => $site,
    ],
  ];
}

/**
 * Implements hook_tokens().
 */
function dyniva_core_tokens($type, $tokens, array $data, array $options, BubbleableMetadata $bubbleable_metadata) {
  $replacements = [];

  if ($type == 'site') {
    foreach ($tokens as $name => $original) {
      switch ($name) {
        case 'base_path':
          $replacements[$original] = base_path();
          break;

        case 'site_path':
          $sites_path = \Drupal::service('site.path');
          $replacements[$original] = $sites_path;
          break;
      }
    }
  }
  return $replacements;
}
