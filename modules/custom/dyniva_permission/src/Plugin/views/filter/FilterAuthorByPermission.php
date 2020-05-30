<?php

namespace Drupal\dyniva_permission\Plugin\views\filter;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Database\Database;
use Drupal\views\Plugin\views\filter\FilterPluginBase;
use Drupal\views\Views;

/**
 *
 * @ingroup views_field_handlers
 *
 * @ViewsFilter("filter_author_by_permission")
 */
class FilterAuthorByPermission extends FilterPluginBase {

  /**
   * @var views_plugin_query_default
   */
  public $query;

  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['permission'] = ['default' => ''];

    return $options;
  }

  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);
    $this->view->initStyle();

    $form['permission'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Permission machine name'),
      '#description' => $this->t("Filter author if current user no this permission,keep empty to filter for all users."),
      '#default_value' => $this->options['permission'],
    ];
    
  }
  
  public function query() {
    
    if(!empty($this->options['permission']) && \Drupal::currentUser()->hasPermission($this->options['permission'])){
      return;
    }
    
    $uid = \Drupal::currentUser()->id();
    
    // filter by uid
    $base_type = $this->view->getBaseEntityType();
    if($base_type && $ukey = $base_type->getKey('uid')){
      $table_name = $this->view->storage->get('base_table');
      $table_info = $this->query->getTableInfo($table_name);
      if($table_info){
        $this->query->addWhere($table_info['relationship'],"{$table_info['alias']}.{$ukey}",$uid);
      }
    }else if($base_type && $base_type->id() == 'media'){
      $table_name = $this->view->storage->get('base_table');
      $table_info = $this->query->getTableInfo($table_name);
      if($table_info){
        $this->query->addWhere($table_info['relationship'],"{$table_info['alias']}.{'uid'}",$uid);
      }
    }
    
  }

  public function adminSummary() {
    if ($this->isAGroup()) {
      return $this->t('grouped');
    }
    if (!empty($this->options['exposed'])) {
      return $this->t('exposed');
    }
    
    $output = 'Filter by permission : ' . $this->options['permission'];
    return $output;
  }
  
}
