<?php

namespace Drupal\dyniva_permission\Plugin\views\filter;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Database\Database;
use Drupal\views\Plugin\views\filter\FilterPluginBase;
use Drupal\views\Views;

/**
 * Filter handler which allows to search on multiple fields.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsFilter("dyniva_permission")
 */
class DynivaPermission extends FilterPluginBase {

  /**
   * @var views_plugin_query_default
   */
  public $query;

  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['fields'] = ['default' => []];
    $options['hierarchy'] = ['default' => false];
    $options['hierarchy_level'] = ['default' => 9];

    return $options;
  }

  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);
    $this->view->initStyle();

    // Allow to choose all fields as possible
    if ($this->view->style_plugin->usesFields()) {
      $options = [];
      foreach ($this->view->display_handler->getHandlers('field') as $name => $field) {
        $options[$name] = $field->adminLabel(TRUE);
      }
      if ($options) {
        $form['fields'] = [
          '#type' => 'select',
          '#title' => $this->t('Choose fields for permission filter'),
          '#description' => $this->t("This filter only work for taxonomy term reference filed."),
          '#multiple' => TRUE,
          '#options' => $options,
          '#default_value' => $this->options['fields'],
        ];
      }
      else {
        $form_state->setErrorByName('', $this->t('You have to add some fields to be able to use this filter.'));
      }
    }
    $form['hierarchy'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Filter by hierarchy'),
      '#description' => $this->t("check this for add children items by term hierarchy."),
      '#default_value' => $this->options['hierarchy'],
    ];
    $form['hierarchy_level'] = [
      '#type' => 'number',
      '#max' => 9,
      '#title' => $this->t('Filter by hierarchy max level'),
      '#description' => $this->t("Select max level children items by term hierarchy."),
      '#default_value' => $this->options['hierarchy_level'],
      '#states' => [
        'visible' => [
          'select[name="options[hierarchy]"]' => ['checkd' => TRUE]
        ]
      ]
    ];
    
  }

  public function query() {
    
    $scope = \Drupal::request()->get('scope','all');
    if(\Drupal::currentUser()->hasPermission('by pass ccms premission') && $scope == 'all'){
      return;
    }
    
    $uid = \Drupal::currentUser()->id();
    
    $this->view->_build('field');
    $fields = [];
    // Only add the fields if they have a proper field and table alias.
    foreach ($this->options['fields'] as $id) {
      // Overridden fields can lead to fields missing from a display that are
      // still set in the non-overridden combined filter.
      if (!isset($this->view->field[$id])) {
        // If fields are no longer available that are needed to filter by, make
        // sure no results are shown to prevent displaying more then intended.
        $this->view->build_info['fail'] = TRUE;
        continue;
      }
      $field = $this->view->field[$id];
      // Always add the table of the selected fields to be sure a table alias exists.
      $field->ensureMyTable();
      if (!empty($field->tableAlias) && !empty($field->realField)) {
        
//         $configuration = array(
//           'type'       => 'LEFT',
//           'table'      => 'dyniva_permission',
//           'field'      => 'tid',
//           'left_table' => $field->tableAlias,
//           'left_field' => $field->realField,
//           'operator'   => '=',
//           'extra' => [
//             [
//               'field' => 'uid',
//               'value' => $uid,
//             ],
//           ],
//         );
        
//         $r_name = $field->tableAlias . '_ccms_permisson';
//         $join = Views::pluginManager('join')->createInstance('standard', $configuration);
//         $rel = $this->query->addRelationship($r_name, $join, $field->tableAlias);
//         $this->query->addTable($r_name, $rel, $join, 'release_record');
//         $this->query->addWhere($this->options['group'], "{$r_name}.uid", NULL, 'IS NOT NULL');
        if($this->options['hierarchy']){
          $level = $this->options['hierarchy_level'];
          
          $q0 =  \Drupal::database()->select('dyniva_permission','cp');
          $q0->condition('cp.uid',$uid);
          
          $qr = clone $q0;
          $qr->join('taxonomy_term__parent', 'h1', 'h1.parent_target_id = cp.tid');
          
          $q1 = clone $qr;
          $q0->addField('cp', 'tid');
          $q1->addField('h1', 'entity_id','tid');
          $sub_query = $q0->union($q1);
          for($i=2;$i<=$level;$i++){
            $j = $i-1;
            $qr->join('taxonomy_term__parent', "h{$i}", "h{$i}.parent_target_id = h{$j}.entity_id");
            
            $q = clone $qr;
            $q->addField("h{$i}", 'entity_id','tid');
            $sub_query = $sub_query->union($q);
          }
          
        }else{
          $sub_query = \Drupal::database()->select('dyniva_permission','cp');
          $sub_query->condition('cp.uid',$uid);
          $sub_query->addField('cp', 'tid');
        }
        
        $this->query->addWhere($this->options['group'], "{$field->tableAlias}.{$field->realField}", $sub_query, 'IN');
        
        // filter by uid
        if(!\Drupal::currentUser()->hasPermission('access any content under managed organization')){
          $base_type = $this->view->getBaseEntityType();
          if($base_type->id() == 'media'){
            $ukey = 'uid';
          }else{
            $ukey = $base_type->getKey('uid');
          }
          if($base_type && $ukey){
            $table_name = $this->view->storage->get('base_table');
            $table_info = $this->query->getTableInfo($table_name);
            if($table_info){
              $this->query->addWhere($table_info['relationship'],"{$table_info['alias']}.{$ukey}",$uid);
            }
          }
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function validate() {
    $errors = parent::validate();
    if ($this->displayHandler->usesFields()) {
      $fields = $this->displayHandler->getHandlers('field');
      foreach ($this->options['fields'] as $id) {
        if (!isset($fields[$id])) {
          // Combined field filter only works with fields that are in the field
          // settings.
          $errors[] = $this->t('Field %field set in %filter is not set in display %display.', ['%field' => $id, '%filter' => $this->adminLabel(), '%display' => $this->displayHandler->display['display_title']]);
          break;
        }
      }
    }
    else {
      $errors[] = $this->t('%display: %filter can only be used on displays that use fields. Set the style or row format for that display to one using fields to use the combine field filter.', ['%display' => $this->displayHandler->display['display_title'], '%filter' => $this->adminLabel()]);
    }
    return $errors;
  }

  public function adminSummary() {
    if ($this->isAGroup()) {
      return $this->t('grouped');
    }
    if (!empty($this->options['exposed'])) {
      return $this->t('exposed');
    }
    
    $output = implode(',',$this->options['fields']);
    return $output;
  }
  
}
