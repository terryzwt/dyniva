<?php

namespace Drupal\dyniva_core\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Site info config.
 */
class SiteInfoConfigForm extends ConfigFormBase {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'dyniva_site_info_config_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $config = $this->config('dyniva_core.site_info_config');
    $form = parent::buildForm($form, $form_state);

    $form['disable_login'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Disable Login'),
      '#description' => $this->t('Users are only allowed to login by using one-time link or CAS.'),
      '#default_value' => (bool) $config->get('disable_login'),
    ];
    $form['disable_paste_word'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Disable Paste Tip'),
      '#description' => $this->t('Disable the paste Word prompt.'),
      '#default_value' => $config->get('disable_paste_word') === null?true:(bool) $config->get('disable_paste_word'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('dyniva_core.site_info_config');
    $config->set('disable_login', $form_state->getValue('disable_login'));
    $config->set('disable_paste_word', $form_state->getValue('disable_paste_word'));
    $config->save();

    return parent::submitForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['dyniva_core.site_info_config'];
  }

  /**
   * Check access for a specific request.
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
