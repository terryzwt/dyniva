<?php

namespace Drupal\dyniva_matomo\Plugin\Block;

use Drupal\Core\Form\FormStateInterface;

/**
 * Matomo visits page rank.
 *
 * @Block(
 *  id = "dyniva_matomo_pages",
 *  admin_label = @Translation("Matomo visits page rank"),
 * )
 */
class Pages extends MatomoWidgetBase {
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
    return 'dyniva_matomo_pages_api_callback';
  }
  /**
   * {@inheritDoc}
   */
  public function getApiParams() {
    $params = [
      'expanded' => 1,
      'flat' => 1,
      'filter_limit' => $this->configuration['filter_limit'],
    ];
    if(!empty($this->configuration['date'])) {
      $params['date'] = $this->configuration['date'];
    }
    if(!empty($this->configuration['period'])) {
      $params['period'] = $this->configuration['period'];
    }
    return $params;
  }
  /**
   * {@inheritDoc}
   */
  public function getApiMethod() {
    return 'Actions.getPageUrls';
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
