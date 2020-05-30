<?php

namespace Drupal\dyniva_core\Plugin\Block;

use Drupal\language\ConfigurableLanguageManagerInterface;
use Drupal\user\Plugin\LanguageNegotiation\LanguageNegotiationUserAdmin;
use Drupal\language\Plugin\Block\LanguageBlock;

/**
 * Admin language swatch.
 *
 * @Block(
 *  id = "dyniva_admin_language_switcher",
 *  admin_label = @Translation("Admin Language Switcher"),
 * )
 */
class AdminLanguageSwitcher extends LanguageBlock {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = [];
    $account = \Drupal::currentUser();
    $show_admin_language = FALSE;
    if ($this->languageManager instanceof ConfigurableLanguageManagerInterface) {
      $negotiator = $this->languageManager->getNegotiator();
      $show_admin_language = $negotiator && $negotiator->isNegotiationMethodEnabled(LanguageNegotiationUserAdmin::METHOD_ID);
    }
    $options = [];
    foreach ($this->languageManager->getLanguages() as $language) {
      $options[$language->getId()] = $language->getName();
    }
    $build['language']['preferred_admin_langcode'] = [
      '#type' => 'select',
      '#options' => $options,
      '#value' => $account->getPreferredAdminLangcode(FALSE),
      '#access' => $show_admin_language,
    ];
    $build['#attached']['library'][] = 'dyniva_core/language';
    return $build;
  }

}
