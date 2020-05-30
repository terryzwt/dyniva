<?php

namespace Drupal\qrcode_example;

use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Drupal\dyniva_core\CcmsQrCode;

/**
 * Example of adding qrcode in toolbar.
 */
class Toolbar {

  use StringTranslationTrait;

  /**
   * Variable for current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * Constructs a new Toolbar.
   */
  public function __construct(AccountInterface $current_user) {
    $this->currentUser = $current_user;
  }

  /**
   * Qrcode for toolbar.
   *
   * @see hook_toolbar()
   */
  public function toolbar() {
    $items = [];

    // @todo Permission access.

    $user = \Drupal::currentUser();

    $items['qrcode'] = [
      '#type' => 'toolbar_item',
      '#weight' => 180,
      '#wrapper_attributes' => [
        'class' => ['workspace-toolbar-tab'],
      ],
      '#attached' => [
        'library' => [
          'qrcode_example/qrcode_example',
        ],
      ],
      'tab' => [
        '#type' => 'link',
        '#title' => FALSE,
        '#url' => Url::fromRoute('<current>'),
        '#attributes' => [
          'title' => $this->t('Use your phone to scan qrcode.'),
          'class' => ['toolbar-icon', 'toolbar-icon-workspace'],
          'data-qrcode-src' => file_create_url($this->getQrCode()),
          'data-title' => 'false',
        ],
      ],
    ];

    return $items;
  }

  /**
   * Generate qrcode via Dyniva core qrcode class.
   */
  public function getQrCode() {
    $link = Url::fromRoute('<current>', [], ['absolute' => 'true']);
    $code = CcmsQrCode::fromCurrentUrl();
    if ($code) {
      return $code;
    }
  }

}
