<?php

namespace Drupal\dyniva_matomo\Plugin\Block;

use Drupal\Core\Form\FormStateInterface;

/**
 * Matomo entry pages rank.
 *
 * @Block(
 *  id = "dyniva_matomo_entry_pages",
 *  admin_label = @Translation("Matomo entry pages rank"),
 * )
 */
class EntryPages extends MatomoWidgetBase {
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
    return 'dyniva_matomo_entry_pages_api_callback';
  }
  /**
   * {@inheritDoc}
   */
  public function getApiParams() {
    return [
      'expanded' => 1,
      'flat' => 1,
      'filter_limit' => $this->configuration['filter_limit'],
    ];
  }
  /**
   * {@inheritDoc}
   */
  public function getApiMethod() {
    return 'Actions.getEntryPageUrls';
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
