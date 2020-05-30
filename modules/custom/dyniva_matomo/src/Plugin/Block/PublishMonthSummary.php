<?php


namespace Drupal\dyniva_matomo\Plugin\Block;

use Drupal\dyniva_matomo\Form\MonthToolbarForm;

/**
 * 月度发文统计总览
 *
 * @Block(
 *  id = "dyniva_matomo_publish_month_summary",
 *  admin_label = "月度发文统计总览",
 * )
 */
class PublishMonthSummary extends ToolbarWidgetBase {

  /**
   * {@inheritDoc}
   */
  public function getToolbar() {
    $form = \Drupal::formBuilder()->getForm(MonthToolbarForm::class);
    $id = $this->getWidgetId();
    $form['#attached']['drupalSettings']['dyniva_matomo']['params'][$id] = [
      'date' => $form['date']['#default_value']
    ];
    return $form;
  }

  /**
   * {@inheritDoc}
   */
  public function getApiCallback() {
    return 'dyniva_matomo_widget_publish_month_summary_api_callback';
  }
  /**
   * {@inheritDoc}
   */
  public function getApiParams() {
    return [
      'segment' => 'eventAction==city.content.create',
      'period' => 'day'
    ];
  }
  /**
   * {@inheritDoc}
   */
  public function getApiMethod() {
    return 'Events.getName';
  }

  /**
   * Get block render content.
   */
  public function getContent() {
    $content = parent::getContent();
    $content['#attached']['library'] = ['dyniva_admin/echarts'];
    $content['#prefix'] = "<div class='matomo-widget' id='{$this->getWidgetId()}'>";
    $content['#markup'] = '<div class="chart-wrapper"></div>';
    $content['#suffix'] = "</div>";
    return $content;
  }

}
