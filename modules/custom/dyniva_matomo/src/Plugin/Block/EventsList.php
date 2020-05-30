<?php

namespace Drupal\dyniva_matomo\Plugin\Block;

use Drupal\Core\Form\FormStateInterface;

/**
 * Matomo Events List.
 *
 * @Block(
 *  id = "dyniva_matomo_events_list",
 *  admin_label = @Translation("Matomo Events List"),
 * )
 */
class EventsList extends MatomoWidgetBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'method' => 'Events.getCategoryFromActionId',
      'action' => 'content.create',
      'filter_limit' => 50,
      'table_headers' => '标题,访问量'
    ];
  }

  /**
   * {@inheritDoc}
   */
  public function getContent() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getWidgetSettings() {
    $settings = parent::getWidgetSettings();
    $settings['table_headers'] = $this->configuration['table_headers'];
    return $settings;
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
      '_action' => $this->configuration['action'],
      '_method' => $this->configuration['method'],
      'filter_limit' => $this->configuration['filter_limit']
    ];
  }
  /**
   * {@inheritDoc}
   */
  public function getApiMethod() {
    return 'Custom.getEventsData';
  }
  /**
   *
   * {@inheritDoc}
   * @see \Drupal\Core\Block\BlockBase::blockForm()
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form = parent::blockForm($form, $form_state);
    $form['action'] = array(
      '#title' => t('Event Action'),
      '#type' => 'select',
      '#options' => [
        'content.create' => t('Content Create'),
        'content.update' => t('Content Update'),
        'content.tags' => t('Content Tags'),
        'user.create' => t('User Create'),
        'user.login' => t('User Login'),
      ],
      '#default_value' => !empty($this->configuration['action']) ? $this->configuration['action'] : 'content.create',
    );
    $form['method'] = array(
      '#title' => t('Data Type'),
      '#type' => 'radios',
      '#options' => [
        'Events.getCategoryFromActionId' => t('Event Category'),
        'Events.getNameFromActionId' => t('Event Name'),
      ],
      '#default_value' => !empty($this->configuration['method']) ? $this->configuration['method'] : 'Events.getCategoryFromActionId',
    );
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
    $this->setConfigurationValue('action', $form_state->getValue('action'));
    $this->setConfigurationValue('method', $form_state->getValue('method'));
    $this->setConfigurationValue('filter_limit', $form_state->getValue('filter_limit'));
  }
}
