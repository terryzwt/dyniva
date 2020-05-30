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
 * @ViewsFilter("filter_author_by_scope")
 */
class FilterAuthorByScope extends FilterPluginBase {

  /**
   * @var views_plugin_query_default
   */
  public $query;

  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['scope'] = ['default' => 'all'];

    return $options;
  }

  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);
    $this->view->initStyle();

    $form['scope'] = [
      '#type' => 'radios',
      '#title' => $this->t('Default Scope'),
      '#options' => [
        'my' => $this->t('Filter by author current user(scope=my)'),
        'manage' => $this->t('Filter by dyniva permission scope(scope=manage)'),
        'all' => $this->t('Show all(scope=all) ,default'),
      ],
      '#disabled' => TRUE,
      '#description' => $this->t("Filter author with the scope parameter in request."),
      '#default_value' => $this->options['scope'],
    ];
    
  }

  public function query() {
    
    $scope = \Drupal::request()->get('scope',false)?:$this->options['scope'];
    if($scope != 'my'){
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
    }
  }

  public function adminSummary() {
    if ($this->isAGroup()) {
      return $this->t('grouped');
    }
    if (!empty($this->options['exposed'])) {
      return $this->t('exposed');
    }
    
    $output = 'Filter by scope : ' . $this->options['scope'];
    return $output;
  }
  
}
