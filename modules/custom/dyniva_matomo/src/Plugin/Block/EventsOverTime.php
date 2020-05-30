<?php

namespace Drupal\dyniva_matomo\Plugin\Block;

use Drupal\Core\Form\FormStateInterface;

/**
 * Matomo Events Over Time.
 *
 * @Block(
 *  id = "dyniva_matomo_events_over_time",
 *  admin_label = @Translation("Matomo Events over time"),
 * )
 */
class EventsOverTime extends EventsList {
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
   * {@inheritDoc}
   */
  public function getApiCallback() {
    return 'dyniva_matomo_events_over_time_api_callback';
  }
  /**
   * {@inheritDoc}
   */
  public function getApiParams() {
    $params =  parent::getApiParams();
    $params['_over_time'] = 1;
    return $params;
  }
  /**
   *
   * {@inheritDoc}
   * @see \Drupal\Core\Block\BlockBase::blockForm()
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form = parent::blockForm($form, $form_state);
    $form['auto_refresh']['#disabled'] = true;
    $form['auto_refresh']['#default_value'] = false;
    return $form;
  }
}
