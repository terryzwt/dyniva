<?php

namespace Drupal\dyniva_matomo\Plugin\Block;

use Drupal\Core\Form\FormStateInterface;

/**
 * Matomo Screen Resolution rank.
 *
 * @Block(
 *  id = "dyniva_matomo_screen_resolution",
 *  admin_label = @Translation("Matomo Screen Resolution rank"),
 * )
 */
class ScreenResolution extends MatomoWidgetBase {
  /**
   * {@inheritDoc}
   */
  public function getContent() {
    return [];
  }
  /**
   * {@inheritDoc}
   */
  public function getApiCallback() {
    return 'dyniva_matomo_screen_resolution_api_callback';
  }
  /**
   * {@inheritDoc}
   */
  public function getApiParams() {
    return [
      'filter_limit' => $this->configuration['filter_limit'],
    ];
  }
  /**
   * {@inheritDoc}
   */
  public function getApiMethod() {
    return 'Resolution.getResolution';
  }
  /**
   *
   * {@inheritDoc}
   * @see \Drupal\Core\Block\BlockBase::blockForm()
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form = parent::blockForm($form, $form_state);
    $form['filter_limit'] = array(
      '#title' => t('Rows limit'),
      '#type' => 'number',
      '#max' => 50,
      '#default_value' => !empty($this->configuration['filter_limit']) ? $this->configuration['filter_limit'] : 10,
    );
    return $form;
  }
  /**
   *
   * {@inheritDoc}
   * @see \Drupal\dyniva_matomo\Plugin\Block\MatomoWidgetBase::blockSubmit()
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    parent::blockSubmit($form, $form_state);
    $this->setConfigurationValue('filter_limit', $form_state->getValue('filter_limit'));
  }
}
