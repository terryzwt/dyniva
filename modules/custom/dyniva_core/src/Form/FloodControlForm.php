<?php

namespace Drupal\dyniva_core\Form;

use Drupal\Core\Flood\FloodInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Foold control settings.
 */
class FloodControlForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function __construct(FloodInterface $flood, ConfigFactoryInterface $config_factory) {
    $this->configFactory = $config_factory;
    $this->flood = $flood;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('flood'),
      $container->get('config.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'flood_control_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'user.flood',
      'contact.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $flood_config = $this->config('user.flood');
    $flood_contact_config = $this->config('contact.settings');

    $form = [];

    $form['login'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Login'),
    ];

    $form['login']['user_failed_login_ip_limit'] = [
      '#type' => 'select',
      '#title' => t('Failed login (IP) limit'),
      '#options' => $this->getMapAssoc(),
      '#default_value' => $flood_config->get('ip_limit'),
    ];

    $form['login']['user_failed_login_ip_window'] = [
      '#type' => 'select',
      '#title' => t('Failed login (IP) window'),
      '#options' => $this->getDateFormattedList(TRUE),
      '#default_value' => $flood_config->get('ip_window'),
    ];

    $form['login']['user_failed_login_user_limit'] = [
      '#type' => 'select',
      '#title' => t('Failed login (username) limit'),
      '#options' => $this->getMapAssoc(),
      '#default_value' => $flood_config->get('user_limit'),
    ];

    $form['login']['user_failed_login_user_window'] = [
      '#type' => 'select',
      '#title' => t('Failed login (username) window'),
      '#options' => $this->getDateFormattedList(TRUE),
      '#default_value' => $flood_config->get('user_window'),
    ];

    $form['contact'] = [
      '#type' => 'fieldset',
      '#title' => t('Contact forms'),
    ];

    $form['contact']['contact_threshold_limit'] = [
      '#type' => 'select',
      '#title' => t('Sending e-mails limit'),
      '#options' => $this->getMapAssoc(),
      '#default_value' => $flood_contact_config->get('flood.limit'),
    ];

    $form['contact']['contact_threshold_window'] = [
      '#type' => 'select',
      '#title' => t('Sending e-mails window'),
      '#options' => $this->getDateFormattedList(TRUE),
      '#default_value' => $flood_contact_config->get('flood.interval'),
    ];

    $form['actions']['clear'] = [
      '#type' => 'submit',
      '#value' => t('Clear Floods'),
      '#submit' => ['::clearFlood'],
      '#weight' => 99,
      '#attributes' => ['class' => ['button--danger']],
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
  }

  /**
   * {@inheritdoc}
   */
  public function clearFlood(array &$form, FormStateInterface $form_state) {
    \Drupal::database()->truncate('flood')->execute();
    drupal_set_message(t('Flood records cleared.'));
  }
  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->configFactory->getEditable('user.flood');
    if ($form_state->hasValue('user_failed_login_ip_limit')) {
      $config->set('ip_limit', $form_state->getValue('user_failed_login_ip_limit'));
    }
    if ($form_state->hasValue('user_failed_login_ip_window')) {
      $config->set('ip_window', $form_state->getValue('user_failed_login_ip_window'));
    }
    if ($form_state->hasValue('user_failed_login_user_limit')) {
      $config->set('user_limit', $form_state->getValue('user_failed_login_user_limit'));
    }
    if ($form_state->hasValue('user_failed_login_user_window')) {
      $config->set('user_window', $form_state->getValue('user_failed_login_user_window'));
    }
    $config->save();

    $config = $this->configFactory->getEditable('contact.settings');
    if ($form_state->hasValue('contact_threshold_limit')) {
      $config->set('flood.limit', $form_state->getValue('contact_threshold_limit'));
    }
    if ($form_state->hasValue('contact_threshold_window')) {
      $config->set('flood.interval', $form_state->getValue('contact_threshold_window'));
    }
    $config->save();

    drupal_set_message($this->t('The flood configration have been saved.'));
  }

  /**
   * Forms an associative array from a linear array.
   */
  protected function getMapAssoc() {
    $array = [
      1, 2, 3, 4, 5, 6, 7, 8, 9, 10,
      20, 30, 40, 50, 75, 100,
      125, 150, 200, 250, 500,
    ];
    $array = !empty($array) ? array_combine($array, $array) : [];
    return $array;
  }

  /**
   * Converts timestamps to date formats.
   */
  protected function getDateFormattedList($showSelect = FALSE) {
    $date_formatter = \Drupal::service('date.formatter');
    $timestamps = [
      60, 180, 300, 600, 900, 1800, 2700, 3600,
      10800, 21600, 32400, 43200, 86400,
    ];

    $list = [];

    // Add the "None (disabled)" as first option.
    if (filter_var($showSelect, FILTER_VALIDATE_BOOLEAN)) {
      $list['0'] = $this->t('None (disabled)');
    }

    // Append all timestamps.
    foreach ($timestamps as $key) {
      $list[$key] = $date_formatter->formatInterval($key);
    }

    return $list;
  }

  /**
   * Check access for flood manage.
   *
   * @param \DRupal\Core\Session\AccountInterface $account
   *   Run access check for this account.
   */
  public function access(AccountInterface $account) {
    $result = FALSE;

    if (in_array('administrator', $account->getRoles())) {
      $result = TRUE;
    }
    if (in_array('webmaster', $account->getRoles())) {
      $result = TRUE;
    }

    return AccessResult::allowedIf($result);
  }

}
