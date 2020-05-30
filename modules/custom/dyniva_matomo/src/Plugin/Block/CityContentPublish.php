<?php

namespace Drupal\dyniva_matomo\Plugin\Block;

use Drupal\dyniva_matomo\Form\RangeToolbarForm;

/**
 * 市县年度发文排行榜Top8
 *
 * @Block(
 *  id = "dyniva_matomo_city_content_publish",
 *  admin_label = "市县年度发文排行榜Top8",
 * )
 */
class CityContentPublish extends ToolbarWidgetBase {

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
   * {@inheritDoc}
   */
  public function getApiCallback() {
    return 'dyniva_matomo_widget_city_content_publish_api_callback';
  }
  /**
   * {@inheritDoc}
   */
  public function getApiParams() {
    return [
      'segment' => 'eventAction==city.content.create',
      'flat' => 1,
      'secondaryDimension' => 'eventCategory',
      'period' => 'day'
    ];
  }
  /**
   * {@inheritDoc}
   */
  public function getApiMethod() {
    return 'Events.getName';
  }

  public function getContent() {
    $id = $this->getWidgetId();
    return [
      '#markup' => '<div class="chart-wrapper"></div>',
      '#attached' => [
        'library' => ['dyniva_admin/echarts']
      ],
      '#prefix' => "<div class='matomo-widget' id='{$id}'>",
      '#suffix' => "</div>",
    ];
  }
}
