<?php

namespace Drupal\dyniva_core\Form;

use Drupal\menu_ui\MenuForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Menu from.
 */
class CcmsMenuForm extends MenuForm {

  /**
   * {@inheritdoc}
   */
  protected function buildOverviewTreeForm($tree, $delta) {
    $form = parent::buildOverviewTreeForm($tree, $delta);
    $current_path = \Drupal::request()->getPathInfo();
    if(preg_match('#^/manage/#', $current_path)) {
      foreach ($form as $id => &$item) {
        if(!empty($item['operations']['#links'])){
          $item['operations']['#links']['edit']['url'] = Url::fromRoute('dyniva_core.manage_menu.item_edit',$item['operations']['#links']['edit']['url']->getRouteParameters());
          $item['operations']['#links']['delete']['url'] = Url::fromRoute('dyniva_core.manage_menu.item_delete',$item['operations']['#links']['delete']['url']->getRouteParameters());
        }
      }
    }
    return $form;
  }
  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    parent::save($form, $form_state);

    $form_state->setRedirectUrl(Url::fromRouteMatch(\Drupal::routeMatch()));
  }

}
