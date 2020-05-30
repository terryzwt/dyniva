<?php


namespace Drupal\dyniva_matomo\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\EntityInterface;

class MonthToolbarForm extends AnalyticsToolbarForm {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, EntityInterface $entity = null) {
    $form['date'] = [
      '#type' => 'date',
      '#date_date_format' => 'Y-m',
      '#title' => t('Date'),
      '#default_value' => date('Y-m'),
      '#attributes' => [
        'type' => 'month'
      ]
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
