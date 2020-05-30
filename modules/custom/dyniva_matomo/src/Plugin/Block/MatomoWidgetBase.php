<?php

namespace Drupal\dyniva_matomo\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Utility\Html;

/**
 * Matomo widget base.
 */
abstract class MatomoWidgetBase extends BlockBase {

  /**
   * @var String
   */
  private $widget_id;

  /**
   * 将会提交到matomo api的参数，最高优先级
   */
  abstract public function getApiParams();
  /**
   * Get matomo api data js callback function name.
   * @see toolbar.js
   */
  abstract public function getApiCallback();
  /**
   * Get matomo api method name.
   */
  abstract public function getApiMethod();

  /**
   * Get widget id.
   *
   * @return string
   */
  public function getWidgetId() {
    if(!isset($this->widget_id)) {
      $id = Html::getUniqueId($this->getBaseId());
      $this->widget_id = $id;
    }
    return $this->widget_id;
  }
  /**
   * Get block render content.
   */
  public function getContent() {
    return [
      '#theme' => $this->getBaseId()
    ];
  }

  /**
   * 向前端提供的配置数据.
   */
  public function getWidgetSettings() {
    return [
      'auto_refresh' => $this->configuration['auto_refresh'],
      'refresh_interval' => $this->configuration['refresh_interval'],
      'api_method' => $this->getApiMethod(),
      'api_callback' => $this->getApiCallback(),
      'params' => $this->getApiParams()
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $id = $this->getWidgetId();
    $content = $this->getContent() + [
      '#prefix' => "<div class='matomo-widget' id='{$id}'>",
      '#suffix' => "</div>",
      '#weight' => 2
    ];
    $build = [
      'content' => $content
    ];
    $build['#attached']['drupalSettings']['dyniva_matomo']['widgets'][$id] = $this->getWidgetSettings();
    return $build;
  }
  /**
   *
   * {@inheritDoc}
   * @see \Drupal\Core\Block\BlockBase::blockForm()
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form = parent::blockForm($form, $form_state);
    $form['auto_refresh'] = array(
      '#title' => t('Auto Refresh'),
      '#type' => 'checkbox',
      '#default_value' => !empty($this->configuration['auto_refresh']) ? $this->configuration['auto_refresh'] : false,
    );
    $form['refresh_interval'] = array(
      '#title' => t('Refresh Interval'),
      '#field_suffix' => 's',
      '#type' => 'number',
      '#min' => 5,
      '#default_value' => !empty($this->configuration['refresh_interval']) ? $this->configuration['refresh_interval'] : 60,
    );
    return $form;
  }
  /**
   *
   * {@inheritDoc}
   * @see \Drupal\Core\Block\BlockBase::blockSubmit()
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    parent::blockSubmit($form, $form_state);
    $this->setConfigurationValue('auto_refresh', $form_state->getValue('auto_refresh'));
    $this->setConfigurationValue('refresh_interval', $form_state->getValue('refresh_interval'));
  }
}
