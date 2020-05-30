<?php

namespace Drupal\dyniva_permission\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\user\Entity\User;
use Drupal\user\Entity\Role;
use Drupal\taxonomy\TermStorage;
use Drupal\multiselect\Element\Multiselect;

/**
 * Class BaseImportForm.
 *
 * @package Drupal\dyniva_permission\Form
 */
class DynivaAssignRoleForm extends FormBase {

  protected $user = null;

  /**
   *
   * {@inheritdoc}
   *
   */
  public function getFormId() {
    return 'dyniva_permission_assign_role_form';
  }

  /**
   *
   * {@inheritdoc}
   *
   */
  public function buildForm(array $form, FormStateInterface $form_state, User $user = NULL, $vid = NULL) {
    $this->user = $user;

    if(empty($vid)){
      $vid = \Drupal::state()->get('dyniva_permission.permission_vid','department');
    }
    /**
     *
     * @var TermStorage $term_storage
     */
    $term_storage = \Drupal::entityTypeManager()->getStorage('taxonomy_term');
    $terms = $term_storage->loadTree($vid);
    $v = taxonomy_vocabulary_load($vid);
    $term_options = [];
    foreach ($terms as $item){
      $term_options[$item->tid] = str_repeat('-',$item->depth) . $item->name;
    }
    $form['group'] = array(
      '#type'=>'container',
      '#attributes' => array(
        'class' => 'clearfix',
      )
    );
    $default_tid = empty($this->user->{$vid})?NULL:$this->user->{$vid}->target_id;
    $form['group']['tid'] = array(
      '#type' => 'select',
      '#options' => $term_options,
      '#title' => $v->label(),
      '#required' => TRUE,
      '#disabled' => !\Drupal::currentUser()->hasPermission('by pass ccms premission'),
      '#default_value' => $default_tid,
      '#multiple' => TRUE,
      '#description' => t('Select the organization to manage.'),
    );

    $roles = user_roles(TRUE);
    unset($roles[Role::AUTHENTICATED_ID]);
    $custom_locked_roles = \Drupal::state()->get('dyniva_permission.locked_roles',[]);
    $role_options = [];
    foreach ($roles as $role){
      if($role->hasPermission('by pass ccms premission')) continue;
      if(in_array($role->id(),$custom_locked_roles)) continue;
      $role_options[$role->id()] = $role->label();
    }
    $form['group']['rid'] = array(
      '#type' => 'multiselect',
      '#options' => $role_options,
      '#title' => t('Roles'),
      '#name' => 'rid',
      '#multiple' => TRUE,
    );
    Multiselect::processSelect($form['group']['rid'], $form_state, $form);

    $form['description'] = array(
      '#type' => 'markup',
      '#markup'=> '<div class="description">' . t('Select the roles for this organization.') . '</div>'
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
    $uid = $this->user->id();
    $tids = $form_state->getValue('tid',[]);
    $storage = \Drupal::entityTypeManager()->getStorage('dyniva_permission');
    if(!empty($tids) && !empty($roles)){
      foreach ($tids as $tid){
        foreach($roles as $rid){
          dyniva_permission_add_permission($uid, $tid, $rid);
        }
      }
    }
    drupal_set_message(t('Assign roles for @label successful.',['@label' => $this->user->getDisplayName()]));
    $form_state->setRedirect('view.user_manage_roles.page_list',['user' => $this->user->id()]);
  }

  /**
   *
   * {@inheritdoc}
   *
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    if(empty($form_state->getValue('rid'))){
      $form_state->setErrorByName('rid',t('Please select roles.'));
    }
  }
}
