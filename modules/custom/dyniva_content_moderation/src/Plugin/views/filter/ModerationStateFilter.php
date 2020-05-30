<?php

namespace Drupal\dyniva_content_moderation\Plugin\views\filter;

use Drupal\views\Plugin\views\filter\InOperator;
use Drupal\views\Views;
use Drupal\views\ViewExecutable;
use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\Core\Form\OptGroup;
use Drupal\views\Plugin\views\filter\FilterPluginBase;

/**
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("content_moderation_state_select")
 */
class ModerationStateFilter extends InOperator {
 
  /**
   * {@inheritdoc}
   */
  public function init(ViewExecutable $view, DisplayPluginBase $display, array &$options = NULL) {
    parent::init($view, $display, $options);
    $this->definition['options callback'] = array($this, 'generateOptions');
  }
  
  /**
   * Helper function that generates the options.
   * @return array
   */
  public function generateOptions() {
    $options = [];
    $workflows = \Drupal::entityTypeManager()->getStorage('workflow')->loadMultiple();
    foreach ($workflows as $workflow){
      $destinations = $workflow->getTypePlugin()->getStates();
      foreach ($destinations as $item){
        $options[$item->id()] = $this->t($item->label());
      }
    }
    return $options;
  }
  public function validate() {
    $this->getValueOptions();
    $errors = FilterPluginBase::validate();
    return $errors;
  }
}
