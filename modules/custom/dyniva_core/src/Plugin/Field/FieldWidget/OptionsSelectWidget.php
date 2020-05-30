<?php

namespace Drupal\dyniva_core\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Field\Plugin\Field\FieldWidget\OptionsSelectWidget as SelectBase;
use Drupal\Core\Entity\FieldableEntityInterface;

/**
 * Custom select options widget.
 *
 * @FieldWidget(
 *   id = "ccms_options_select",
 *   label = @Translation("Ccms Select list"),
 *   field_types = {
 *     "entity_reference",
 *   },
 *   multiple_values = TRUE
 * )
 */
class OptionsSelectWidget extends SelectBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'force_deepest' => 0,
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element['force_deepest'] = [
      '#type' => 'checkbox',
      '#title' => t('Force Deepest'),
      '#default_value' => $this->getSetting('force_deepest'),
    ];
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];

    $placeholder = $this->getSetting('force_deepest');
    if (!empty($placeholder)) {
      $summary[] = t('Force Deepest');
    }

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element = parent::formElement($items, $delta, $element, $form, $form_state);

    $element['#options_attributes'] = $this->getOptionsAttributes($items->getEntity());

    $element['#attributes']['class'][] = 'ccms-options-list';

    if ($this->getSetting('force_deepest')) {
      $element['#attributes']['class'][] = 'disabled-parent-category';
    }

    return $element;
  }

  /**
   * Get options attributes.
   *
   * @param \Drupal\Core\Entity\FieldableEntityInterface $entity
   *   Entity.
   *
   * @return string|NULL[][]
   *   Options attributes.
   */
  protected function getOptionsAttributes(FieldableEntityInterface $entity) {
    $settings = $this->fieldDefinition->getSettings();
    $attributes = [];
    if (!empty($settings['target_type']) && $settings['target_type'] == 'taxonomy_term') {
      foreach ($settings['handler_settings']['target_bundles'] as $vid) {
        $tree = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadTree($vid, 0);
        foreach ($tree as $item) {
          $attributes[$item->tid] = [
            'data-parent' => reset($item->parents),
            'data-depth' => $item->depth,
          ];
          if ($this->getSetting('force_deepest')) {
            $sub_tree = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadTree($vid, $item->tid, 1);
            if (!empty($sub_tree)) {
              $attributes[$item->tid]['disabled'] = 'disabled';
            }
          }
        }
      }
    }
    return $attributes;
  }

}
