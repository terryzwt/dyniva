<?php

namespace Drupal\dyniva_content_moderation\Plugin\views\field;

use Drupal\system\Plugin\views\field\BulkForm;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ViewExecutable;
use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Defines a moderate operations bulk form element.
 *
 * @ViewsField("moderate_bulk_form")
 */
class ModerateBulkForm extends BulkForm {

  /**
   * {@inheritdoc}
   */
  public function init(ViewExecutable $view, DisplayPluginBase $display, array &$options = NULL) {
    FieldPluginBase::init($view, $display, $options);
  
    $entity_type = $this->getEntityType();
    // Filter the actions to only include those for this entity type.
    $acts = \Drupal::service('plugin.manager.action')->getDefinitionsByType($entity_type);
    $this->actions = $this->actionStorage->loadMultiple(array_keys($acts));
  }
  /**
   * {@inheritdoc}
   */
  protected function emptySelectedMessage() {
    return $this->t('No content selected.');
  }
  /**
   * 
   * {@inheritDoc}
   * @see \Drupal\system\Plugin\views\field\BulkForm::getBulkOptions()
   */
  protected function getBulkOptions($filtered = TRUE) {
    $options = array('_none' => '- Select action -');
    // Filter the action list.
    foreach ($this->actions as $id => $action) {
      if ($filtered) {
        $in_selected = in_array($id, $this->options['selected_actions']);
        // If the field is configured to include only the selected actions,
        // skip actions that were not selected.
        if (($this->options['include_exclude'] == 'include') && !$in_selected) {
          continue;
        }
        // Otherwise, if the field is configured to exclude the selected
        // actions, skip actions that were selected.
        elseif (($this->options['include_exclude'] == 'exclude') && $in_selected) {
          continue;
        }
      }
  
      $plugin = $action->getPlugin();
      if($plugin->actionAccess()){
        $options[$id] = $action->label();
      }
    }
  
    return $options;
  }
  /**
   * {@inheritdoc}
   */
  public function viewsFormValidate(&$form, FormStateInterface $form_state) {
    $action = $form_state->getValue('action');
    if ($action == '_none') {
      $form_state->setErrorByName('', $this->t('No action selected.'));
    }
    parent::viewsFormValidate($form, $form_state);
  }
}
