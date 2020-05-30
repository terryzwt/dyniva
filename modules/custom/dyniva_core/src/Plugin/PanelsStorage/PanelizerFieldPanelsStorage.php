<?php

namespace Drupal\dyniva_core\Plugin\PanelsStorage;

use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Plugin\Context\ContextDefinition;
use Drupal\ctools\Context\AutomaticContext;
use Drupal\panelizer\Plugin\PanelsStorage\PanelizerFieldPanelsStorage as PanelizerFieldPanelsStorageBase;

/**
 * Panels storage service that stores Panels displays in the Panelizer field.
 */
class PanelizerFieldPanelsStorage extends PanelizerFieldPanelsStorageBase {

  /**
   * {@inheritdoc}
   */
  public function load($id) {
    $panels_display = parent::load($id);
    $contexts = $panels_display->getContexts();
    $langcode = \Drupal::languageManager()->getCurrentLanguage(LanguageInterface::TYPE_CONTENT);
    $contexts['current_language'] = new AutomaticContext(new ContextDefinition('language', NULL, TRUE), $langcode);
    $panels_display->setContexts($contexts);
    return $panels_display;
  }

}
