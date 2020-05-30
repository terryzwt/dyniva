<?php

namespace Drupal\dyniva_matomo\Plugin\Block;

use Drupal\Core\Form\FormStateInterface;

/**
 * Matomo entry pages rank.
 *
 * @Block(
 *  id = "dyniva_matomo_search_keywords_no_results",
 *  admin_label = @Translation("Matomo search keywords no results"),
 * )
 */
class SearchKeywordsNoResults extends MatomoWidgetBase {
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
    return 'dyniva_matomo_events_list_api_callback';
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
    return 'Actions.getSiteSearchNoResultKeywords';
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
