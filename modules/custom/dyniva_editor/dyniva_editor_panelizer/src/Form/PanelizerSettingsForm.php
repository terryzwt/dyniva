<?php

namespace Drupal\dyniva_editor_panelizer\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * {@inheritdoc}
 */
class PanelizerSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'dyniva_editor_panelizer_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'dyniva_editor_panelizer.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->configFactory()->get('dyniva_editor_panelizer.settings');

    $blockManager = \Drupal::service('plugin.manager.block');
    // $panelsStorage = \Drupal::service('panels.storage_manager');
    // $panels_storage_type = 'panelizer_default';
    // $panels_storage_id = '*node:19:full:default';
    // $panels_display = $panelsStorage->load($panels_storage_type, $panels_storage_id);
    // $definitions = $blockManager->getDefinitionsForContexts($panels_display->getContexts());
    $definitions = $blockManager->getDefinitionsForContexts([]);
    $keys = [];
    foreach($definitions as $definition) {
        $keys []= $definition['provider'];
    }
    $options = [];
    $keys = array_unique($keys);
    foreach($keys as $key) {
      $options[$key] = $key;
    }

    $form['disable_blocks'] = [
      '#type' => 'checkboxes',
      '#title' => t('Disable blocks for panelizer'),
      '#options' => $options,
      '#default_value' => $config->get('disable_blocks') ?: [],
    ];

    $form['actions'] = [
      '#type' => 'actions',
      '#attributes' => [
        'class' => ['clearfix'],
      ],
    ];

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $config = $this->configFactory()->getEditable('dyniva_editor_panelizer.settings');
    foreach (['disable_blocks'] as $key) {
      $config->set($key, array_filter(array_values($values[$key])));
    }

    $config->save();
    parent::submitForm($form, $form_state);
  }
}
