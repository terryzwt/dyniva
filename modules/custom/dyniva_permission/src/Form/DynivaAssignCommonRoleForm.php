<?php

namespace Drupal\dyniva_permission\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\user\Entity\User;
use Drupal\user\Entity\Role;
use Drupal\taxonomy\TermStorage;
use Drupal\Core\Access\AccessResult;

/**
 * Class BaseImportForm.
 *
 * @package Drupal\dyniva_permission\Form
 */
class DynivaAssignCommonRoleForm extends FormBase {

  protected $user = null;

  /**
   *
   * {@inheritdoc}
   *
   */
  public function getFormId() {
    return 'dyniva_permission_assign_common_role_form';
  }

  /**
   *
   * {@inheritdoc}
   *
   */
  public function buildForm(array $form, FormStateInterface $form_state, User $user = NULL) {
    $this->user = $user;


    $form['group'] = array(
      '#type'=>'details',
      '#title' => t('User Common Roles'),
      '#open' => TRUE,
      '#attributes' => array(
        'class' => 'clearfix',
      )
    );
    $form['group']['rid'] = array(
      '#type' => 'checkboxes',
      '#options' => $this->getCommonRoles(),
      '#title' => t('Roles'),
      '#default_value' => $user->getRoles(),
    );

    $form['actions'] = array(
      '#type' => 'actions',
      '#attributes' => array(
        'class'=> array('clearfix'),
      )
    );
    $form['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
    );
    return $form;
  }

  /**
   *
   * {@inheritdoc}
   *
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $roles = $form_state->getValue('rid',[]);
    foreach ($roles as $rid => $status){
      if($status){
        $this->user->addRole($rid);
      }else{
        $this->user->removeRole($rid);
      }
    }
    $this->user->save();
    drupal_set_message(t('Assign roles for @label successful.',['@label' => $this->user->getDisplayName()]));
    $form_state->setRedirect('view.manage_user.page_list',['user' => $this->user->id()]);
  }

  public function getCommonRoles(){
    $role_options = [];
    $roles = user_roles(TRUE);
    unset($roles[Role::AUTHENTICATED_ID]);
    $custom_locked_roles = \Drupal::state()->get('dyniva_permission.locked_roles',[]);
    foreach ($roles as $role){
      if($role->hasPermission('by pass ccms premission') || in_array($role->id(),$custom_locked_roles)){
        if($role->id() == 'webmaster' || $role->id() == 'administrator') continue;
        $role_options[$role->id()] = $role->label();
      }
    }
    return $role_options;
  }
  public function formAccess(User $user = null) {
    if(\Drupal::currentUser()->hasPermission('manage ccms user') && !empty($this->getCommonRoles())){
      return AccessResult::allowed();
    }
    return AccessResult::forbidden();
  }
}
