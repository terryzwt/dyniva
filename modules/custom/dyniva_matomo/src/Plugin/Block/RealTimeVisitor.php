<?php

namespace Drupal\dyniva_matomo\Plugin\Block;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Utility\Html;

/**
 * Matomo Real time visitor.
 *
 * @Block(
 *  id = "dyniva_matomo_real_time_visitor",
 *  admin_label = @Translation("Matomo Real time visitor"),
 * )
 */
class RealTimeVisitor extends MatomoWidgetBase {
  /**
   * {@inheritDoc}
   */
  public function getApiCallback() {
    return 'dyniva_matomo_widget_real_time_visitor_api_callback';
  }
  /**
   * {@inheritDoc}
   */
  public function getApiParams() {
    return [
      'lastMinutes' => $this->configuration['lastMinutes'],
    ];
  }
  /**
   * {@inheritDoc}
   */
  public function getApiMethod() {
    return 'Live.getCounters';
  }
  /**
   * 
   * {@inheritDoc}
   * @see \Drupal\Core\Block\BlockBase::blockForm()
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form = parent::blockForm($form, $form_state);
    $form['lastMinutes'] = array(
      '#title' => t('Last Minutes'),
      '#field_suffix' => 'mins',
      '#type' => 'number',
      '#default_value' => !empty($this->configuration['lastMinutes']) ? $this->configuration['lastMinutes'] : 5,
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
    $this->setConfigurationValue('lastMinutes', $form_state->getValue('lastMinutes'));
  }
}
