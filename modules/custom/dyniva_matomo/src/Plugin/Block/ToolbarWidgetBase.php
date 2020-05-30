<?php


namespace Drupal\dyniva_matomo\Plugin\Block;

use Drupal\Component\Utility\Html;
use Drupal\Core\Url;

abstract class ToolbarWidgetBase extends MatomoWidgetBase {

  /**
   * @return array
   */
  abstract public function getToolbar();

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = parent::build();
    $id = $this->getWidgetId();
    $form = $this->getToolbar();
    $form['#attributes']['data-type'] = 'block-toolbar';
    $form['#attributes']['data-id'] = $id;

    // toolbar.js
    $form['#attached']['library'][] = 'dyniva_matomo/toolbar';
    // params全局默认值，将会提交到matomo API。表单的选项更新与block自身的api params都可能会影响最终提交到matomo的参数.
    $form['#attached']['drupalSettings']['dyniva_matomo']['api'] = Url::fromRoute('dyniva_matomo.matomo_api')->toString();

    $build['top'] = $form;
    $build['top']['#weight'] = 1;
    return $build;
  }

}
