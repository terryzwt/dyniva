<?php

namespace Drupal\dyniva_permission\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\user\Entity\User;
use Drupal\user\Entity\Role;
use Drupal\taxonomy\TermStorage;
use Drupal\multiselect\Element\Multiselect;
use Drupal\taxonomy\Entity\Vocabulary;

/**
 * Class BaseImportForm.
 *
 * @package Drupal\dyniva_permission\Form
 */
class DynivaPermissionSettingsForm extends FormBase {


  /**
   *
   * {@inheritdoc}
   *
   */
  public function getFormId() {
    return 'dyniva_permission_settings_form';
  }

  /**
   *
   * {@inheritdoc}
   *
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $roles = user_roles(TRUE);
    unset($roles[Role::AUTHENTICATED_ID]);
    $custom_locked_roles = \Drupal::state()->get('dyniva_permission.locked_roles',[]);
    $role_options = [];
    foreach ($roles as $role){
      if($role->hasPermission('by pass ccms premission')) continue;
      $role_options[$role->id()] = $role->label();
    }
    $form['rid'] = array(
      '#type' => 'select',
      '#options' => $role_options,
      '#title' => t('Locked Roles'),
      '#multiple' => TRUE,
      '#description' => t('Select roles unmanaged in dyniva premission.'),
      '#default_value' => $custom_locked_roles,
    );
    
    $permission_vid = \Drupal::state()->get('dyniva_permission.permission_vid','department');
    $enable_field_limit = \Drupal::state()->get('dyniva_permission.enable_field_limit', 0);
    $permission_field_name = \Drupal::state()->get('dyniva_permission.permission_field_name','department');
    
    
    $vocabulary_options = [];
    $vodcabularys = Vocabulary::loadMultiple();
    foreach ($vodcabularys as $v){
      $vocabulary_options[$v->id()] = $v->label();
    }
    $form['permission_vid'] = array(
      '#type' => 'radios',
      '#options' => $vocabulary_options,
      '#title' => t('Default Permission Vocabulary'),
      '#description' => t('Select default vocabulary used in dyniva premission.'),
      '#default_value' => $permission_vid,
      '#required' => TRUE,
    );
    $form['permission_field'] = array(
      '#type' => 'details',
      '#open' => TRUE,
      '#title' => t('Permission Field'),
    );
    $form['permission_field']['enable_field_limit'] = array(
      '#type' => 'checkbox',
      '#title' => t('Enable Permission Field'),
      '#default_value' => $enable_field_limit
    );
    $form['permission_field']['permission_field_name'] = array(
      '#type' => 'textfield',
      '#title' => t('Permission Field Name'),
      '#default_value' => $permission_field_name
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
    \Drupal::state()->set('dyniva_permission.locked_roles',$roles);
    \Drupal::state()->set('dyniva_permission.permission_vid',$form_state->getValue('permission_vid'));
    \Drupal::state()->set('dyniva_permission.enable_field_limit',$form_state->getValue('enable_field_limit'));
    \Drupal::state()->set('dyniva_permission.permission_field_name',$form_state->getValue('permission_field_name'));
  }
}
