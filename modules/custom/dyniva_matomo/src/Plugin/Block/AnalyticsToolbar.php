<?php

namespace Drupal\dyniva_matomo\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\dyniva_matomo\Form\AnalyticsToolbarForm;
use Drupal\Component\Utility\Html;
use Drupal\Core\Url;
use Drupal\Core\Form\FormStateInterface;

/**
 * Matomo toolbar.
 *
 * @Block(
 *  id = "dyniva_matomo_analytics_toolbar",
 *  admin_label = @Translation("Matomo analytics toolbar"),
 *  context_definitions = {
 *    "entity" = @ContextDefinition("entity"),
 *  }
 * )
 */
class AnalyticsToolbar extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'date_range' => 0,
      'date_hide' => 0,
      'period_hide' => 0,
      'segment_hide' => 0,
      'idSite_hide' => 0,
      'idSite_all' => 0
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $configuration = $this->getConfiguration();
    $build = [];
    try {
      $entity = $this->getContextValue('entity');
    } catch (\Exception $e) {
      $entity = null;
    }
    $form = \Drupal::formBuilder()->getForm(AnalyticsToolbarForm::class, $entity);

    $id = Html::getUniqueId($this->getBaseId());
    $form['#attributes']['data-id'] = $id;

    // toolbar.js
    $form['#attached']['library'][] = 'dyniva_matomo/toolbar';
    // params全局默认值，将会提交到matomo API。表单的选项更新与block自身的api params都可能会影响最终提交到matomo的参数.
    $form['#attached']['drupalSettings']['dyniva_matomo']['api'] = Url::fromRoute('dyniva_matomo.matomo_api')->toString();

    if($configuration['idSite_all']) {
//      $siteIds = array_keys($form['idSite']['#options']);
//      $form['idSite']['#options'][implode(',', $siteIds)] = t('All site');
//      $form['idSite']['#default_value'] = implode(',', $siteIds);
      $options = ['all' => t('All site')];
      foreach($form['idSite']['#options'] as $key => $value) {
        $options[$key] = $value;
      }
      $form['idSite']['#options'] = $options;
      $form['idSite']['#default_value'] = 'all';
      $form['idSite']['#value'] = 'all';
    }

    $params = [
      'date' => $form['date']['#default_value'],
      'period' => $form['period']['#default_value'],
      'segment' => $form['segment']['#default_value'],
      'idSite' => $form['idSite']['#default_value']
    ];
    if($configuration['date_hide']) {
      unset($form['date']);
      unset($form['date1']);
      unset($form['date2']);
    } elseif($configuration['date_range']) {
      $params['date'] = "{$form['date1']['#default_value']},{$form['date2']['#default_value']}";
      unset($form['date']);
    } else {
      unset($form['date1']);
      unset($form['date2']);
    }
    if($configuration['period_hide']) {
      unset($form['period']);
    }
    if($configuration['segment_hide']) {
      unset($form['segment']);
    }
    if($configuration['idSite_hide']) {
      unset($form['idSite']);
    }
    $form['#attached']['drupalSettings']['dyniva_matomo']['params'][$id] = $params;
    $build['form'] = $form;
    return $build;
  }


  /**
   *
   * {@inheritDoc}
   * @see \Drupal\Core\Block\BlockBase::blockForm()
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form = parent::blockForm($form, $form_state);
    $form['period_hide'] = array(
      '#title' => t('Hide period'),
      '#type' => 'checkbox',
      '#default_value' => $this->configuration['period_hide']
    );
    $form['segment_hide'] = array(
      '#title' => t('Hide segment'),
      '#type' => 'checkbox',
      '#default_value' => $this->configuration['segment_hide']
    );
    $form['idSite_hide'] = array(
      '#title' => t('Hide sites'),
      '#type' => 'checkbox',
      '#default_value' => $this->configuration['idSite_hide']
    );
    return $form;
  }

  /**
   * {@inheritDoc}
   * @see \Drupal\Core\Block\BlockBase::blockSubmit()
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    parent::blockSubmit($form, $form_state);
    $this->setConfigurationValue('period_hide', $form_state->getValue('period_hide'));
    $this->setConfigurationValue('segment_hide', $form_state->getValue('segment_hide'));
    $this->setConfigurationValue('idSite_hide', $form_state->getValue('idSite_hide'));
  }
}
