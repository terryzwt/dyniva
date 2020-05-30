<?php

namespace Drupal\dyniva_core\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\Cache;

/**
 * User info block.
 *
 * @Block(
 *  id = "dyniva_core_user_info",
 *  admin_label = @Translation("User Info"),
 * )
 */
class UserInfo extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = [];
    $build['#theme'] = 'dyniva_core_user_info';
    $build['#cache'] = [
      'contexts' => ['user'],
    ];
    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    $cache_contexts = parent::getCacheContexts();
    $cache_contexts = Cache::mergeContexts($cache_contexts, ['user']);
    return $cache_contexts;
  }

}
