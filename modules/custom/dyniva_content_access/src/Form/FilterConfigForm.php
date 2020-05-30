<?php

/**
 * @file
 */
namespace Drupal\dyniva_content_access\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\FormBase;

class FilterConfigForm extends FormBase {

  /**
   *
   * {@inheritdoc} .
   */
  public function getFormId() {
    return 'dyniva_content_access_filter_settings';
  }

  /**
   *
   * {@inheritdoc} .
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['internal_ip_range'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Internal IP Range'),
      '#default_value' => \Drupal::state()->get('dyniva_content_access.internal_ip_range', ''),
      '#description' => t('IP/CIDR netmask eg. 127.0.0.0/24, also 127.0.0.1 is accepted and /32 assumed,one item per line.')
    ];
    $form['ip_header_tag'] = [
      '#type' => 'textfield',
      '#title' => $this->t('IP tag in header'),
      '#default_value' => \Drupal::state()->get('dyniva_content_access.ip_header_tag', ''),
      '#description' => t('Leave blank to use remote ip address.')
    ];
    $form['debug'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Log debug message'),
      '#default_value' => \Drupal::state()->get('dyniva_content_access.debug', false),
    ];
    
    $form['actions'] = [
      '#type' => 'actions',
      '#weight' => 99
    ];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => t('Submit')
    ];
    return $form;
  }

  /**
   *
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
  }

  /**
   *
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    \Drupal::state()->set('dyniva_content_access.internal_ip_range', $form_state->getValue('internal_ip_range'));
    \Drupal::state()->set('dyniva_content_access.ip_header_tag', $form_state->getValue('ip_header_tag'));
    \Drupal::state()->set('dyniva_content_access.debug', $form_state->getValue('debug'));
    drupal_set_message(t('Save successfuly.'));
  }
}