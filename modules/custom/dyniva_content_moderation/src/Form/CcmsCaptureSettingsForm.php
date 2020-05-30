<?php

namespace Drupal\dyniva_content_moderation\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class CcmsCaptureSettingsForm
 *
 * Provide a settings form for global settings of CCMS Capture.
 *
 * @package Drupal\dyniva_content_moderation\Form
 */
class CcmsCaptureSettingsForm extends ConfigFormBase {

  /**
   * PhantomCaptureSettingsForm constructor.
   * @param ConfigFactoryInterface $config_factory
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    parent::__construct($config_factory);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'dyniva_content_moderation_capture_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['dyniva_content_moderation.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('ccms_capture.settings');
    $url = 'http://phantomjs.org';

    $form['binary'] = array(
      '#type' => 'textfield',
      '#required' => TRUE,
      '#title' => $this->t('Path to PhantomJS binary'),
      '#description' => $this->t('This module requires that you install PhantomJS on your server and enter the path to the executable. The program is not include in the module due to licensing and operation system constraints. See <a href=":url">:url</a> for more information about downloading.', array(
        ':url' => $url,
      )),
      '#default_value' => $config->get('binary'),
    );

    $form['destination'] = array(
      '#type' => 'textfield',
      '#required' => TRUE,
      '#title' => $this->t('Default destination'),
      '#description' => $this->t('The default destination for captures with PhantomJS. Do not include public://. Example, "phantomjs" would be stored as public://phantomjs, or private://phantomjs, based on the site file scheme.'),
      '#default_value' => $config->get('destination'),
    );

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();

    // Check that PhantomJS exists.
    if (!file_exists($values['binary'])) {
      $form_state->setError($form['binary'], $this->t('The PhantomJS binary was not found at the location given.'));
    }

    // Check that destination can be created.
    $destination = \Drupal::config('system.file')->get('default_scheme') . '://' . $values['destination'];
    if (!file_prepare_directory($destination, FILE_CREATE_DIRECTORY)) {
      $form_state->setError($form['destination'], t('The path was not writeable or could not be created.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();

    $this->config('dyniva_content_moderation.settings')
      ->set('binary', $values['binary'])
      ->set('destination', $values['destination'])
      ->save();

    parent::submitForm($form, $form_state);
  }
}
