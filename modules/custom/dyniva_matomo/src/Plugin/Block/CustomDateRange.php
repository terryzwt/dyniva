<?php

namespace Drupal\dyniva_matomo\Plugin\Block;

use Drupal\Core\Form\FormStateInterface;
use Drupal\dyniva_matomo\Form\RangeToolbarForm;

/**
 * Custom block.
 *
 * @Block(
 *  id = "dyniva_matomo_custom_date_range",
 *  admin_label = @Translation("Custom - Date range"),
 * )
 */
class CustomDateRange extends ToolbarWidgetBase {

  /**
   * {@inheritDoc}
   */
  public function getToolbar() {
    $form = \Drupal::formBuilder()->getForm(RangeToolbarForm::class);
    $id = $this->getWidgetId();
    if($form['date1']['#default_value'] != $form['date2']['#default_value']) {
      $form['#attached']['drupalSettings']['dyniva_matomo']['params'][$id] = [
        'date' => "{$form['date1']['#default_value']},{$form['date2']['#default_value']}"
      ];
    } else {
      $form['#attached']['drupalSettings']['dyniva_matomo']['params'][$id] = [
        'date' => $form['date1']['#default_value']
      ];
    }
    unset($form['city']);
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'api_callback' => 'dyniva_matomo_events_list_api_callback',
      'api_method' => 'Events.getName',
      'segment' => 'eventAction==content.create',
      'action' => 'content.create',
      'filter_limit' => 50,
      'table_headers' => '标题,访问量'
    ];
  }

  /**
   * {@inheritDoc}
   */
  public function getContent() {
    return [
      '#markup' => '<div class="chart-wrapper"></div>',
      '#attached' => [
        'library' => ['dyniva_admin/echarts']
      ]
    ];
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
    return $this->configuration['api_callback'];
  }
  /**
   * {@inheritDoc}
   */
  public function getApiParams() {
    $params =  [
      'segment' => $this->configuration['segment'],
      'filter_limit' => $this->configuration['filter_limit']
    ];
    if(!empty($this->configuration['date'])) {
      $params['date'] = $this->configuration['date'];
    }
    return $params;
  }
  /**
   * {@inheritDoc}
   */
  public function getApiMethod() {
    return $this->configuration['api_method'];
  }
  /**
   *
   * {@inheritDoc}
   * @see \Drupal\Core\Block\BlockBase::blockForm()
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form = parent::blockForm($form, $form_state);
    $defaults = $this->defaultConfiguration();
    foreach($defaults as $field => $default) {
      if($field == 'filter_limit') continue;
      $form[$field] = array(
        '#title' => $field,
        '#type' => 'textfield',
        '#default_value' => $this->configuration[$field]
      );
    }
    $form['filter_limit'] = array(
      '#title' => t('Rows limit'),
      '#type' => 'number',
      '#max' => 100,
      '#min' => 0,
      '#default_value' => $this->configuration['filter_limit']
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
    $defaults = $this->defaultConfiguration();
    foreach($defaults as $field => $default) {
      $this->setConfigurationValue($field, $form_state->getValue($field));
    }
  }
}
