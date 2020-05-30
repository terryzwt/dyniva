<?php


namespace Drupal\dyniva_matomo\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\EntityInterface;

class RangeToolbarForm extends AnalyticsToolbarForm {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, EntityInterface $entity = null) {
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
    $form['city'] = [
      '#type' => 'select',
      '#title' => t('City'),
      '#options' => [
        '' => '所有'
      ],
      '#attributes' => [
        'data-action' => 'city'
      ]
    ];
    return $form;
  }

}
