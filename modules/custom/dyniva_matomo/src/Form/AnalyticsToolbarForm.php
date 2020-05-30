<?php

namespace Drupal\dyniva_matomo\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Entity\EntityInterface;

/**
 * 筛选工具，与MatomoWidgetBase类符合使用
 */
class AnalyticsToolbarForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'dyniva_matomo_analytics_toolbar_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, EntityInterface $entity = null) {

    $form['date'] = [
      '#type' => 'date',
      '#date_date_format' => 'Y-m-d',
      '#title' => t('Date'),
      '#default_value' => date('Y-m-d')
    ];
    $form['date1'] = [
      '#type' => 'date',
      '#date_date_format' => 'Y-m-d',
      '#title' => t('Date Start'),
      '#default_value' => date('Y-m-01')
    ];
    $form['date2'] = [
      '#type' => 'date',
      '#date_date_format' => 'Y-m-d',
      '#title' => t('Date End'),
      '#default_value' => date('Y-m-d')
    ];
    $form['period'] = [
      '#type' => 'select',
      '#title' => t('Period'),
      '#options' => [
        'day' => t('Day'),
        'week' => t('Week'),
        'month' => t('Month'),
        'year' => t('Year'),
      ],
      '#default_value' => 'day'
    ];
    $mange_url = urlencode('/manage');
    $form['segment'] = [
      '#type' => 'select',
      '#title' => t('Segment'),
      '#options' => [
        '' => t('All Visits'),
        'deviceType==desktop' => t('Desktop'),
        'deviceType==smartphone' => t('Smart Phone'),
      ],
      '#default_value' => ''
    ];

    if($entity && $entity->bundle() == 'site') {
      $matomo_site_id = $entity->matomo_site_id->value;
      $form['idSite'] = [
        '#type' => 'hidden',
        '#default_value' => $matomo_site_id
      ];
    } else {
      $sites = \Drupal::entityTypeManager()
        ->getStorage('node')
        ->loadByProperties([
          'type' => 'site',
          'status' => 1
        ]);
      $options = [];
      foreach($sites as $site) {
        if($site->hasField('matomo_site_id') && !$site->matomo_site_id->isEmpty()) {
          $options[$site->matomo_site_id->value] = $site->label();
        }
      }
      $keys = array_keys($options);

      $form['idSite'] = [
        '#type' => 'select',
        '#title' => t('Site'),
        '#options' => $options,
        '#default_value' => reset($keys)
      ];
    }

    return $form;
  }
  /**
   *
   * {@inheritDoc}
   * @see \Drupal\Core\Form\FormInterface::submitForm()
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
  }
}
