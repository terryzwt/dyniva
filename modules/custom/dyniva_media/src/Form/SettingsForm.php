<?php

namespace Drupal\dyniva_media\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

class SettingsForm extends ConfigFormBase {

  /**
   * A state that represents the custom settings being enabled.
   */
  const STATE_CUSTOM_SETTINGS = 0;

  /**
   * A state that represents the slideshow being enabled.
   */
  const STATE_SLIDESHOW_ENABLED = 1;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'admin_media_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['file.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $config = $this->configFactory->get('file.settings');
    $form['make_unused_managed_files_temporary'] = [
      '#type' => 'radios',
      '#title' => $this->t('Make unused managed files temporary'),
      '#required' => true,
      '#options' => [0 => 'No', 1 => 'Yes'],
      '#default_value' => $config->get('make_unused_managed_files_temporary') ? 1 : 0,
    ];

    $config = $this->configFactory->get('system.file');
    $form['temporary_maximum_age'] = [
      '#type' => 'number',
      '#title' => $this->t('Temporary maximum age'),
      '#required' => true,
      '#default_value' => $config->get('temporary_maximum_age')
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $config = $this->configFactory->getEditable('file.settings');
    $config->set('make_unused_managed_files_temporary', $form_state->getValue('make_unused_managed_files_temporary'));
    $config->save();

    $config = $this->configFactory->getEditable('system.file');
    $config->set('temporary_maximum_age', $form_state->getValue('temporary_maximum_age'));
    $config->save();

    parent::submitForm($form, $form_state);
  }

}
