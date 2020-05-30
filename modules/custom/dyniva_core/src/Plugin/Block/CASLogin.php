<?php

namespace Drupal\dyniva_core\Plugin\Block;

use Drupal\Core\Url;
use Drupal\Core\Block\BlockBase;

/**
 * Cas login block.
 *
 * @Block(
 *  id = "cas_login",
 *  admin_label = @Translation("CAS Login"),
 * )
 */
class CASLogin extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $config = \Drupal::service('config.factory')->get('dyniva_core.cas.settings');

    if (\Drupal::moduleHandler()->moduleExists('cas')) {
      $url = Url::fromRoute('cas.login')->toString();
    }
    else {
      $url = $config->get('url');
    }

    return [
      '#theme' => 'cas_login',
      '#configure' => [
        'title' => $config->get('title') ? $config->get('title') : '中央认证服务(CAS)登录',
        'subtitle' => $config->get('subtitle') ? $config->get('subtitle') : '管理人员通过NetID',
        'url' => $url,
      ],
    ];
  }

}
