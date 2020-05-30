<?php

/**
 * @file
 * Dispatcher daemon
 * This file is the heart of the dispatcher drush command.
 * It
 * implements most of the backend functionality.
 */

/**
 * Implements hook_drush_command().
 */
function dyniva_core_drush_command() {
  $items = array();
  
  $items['reset-password-by-role'] = array(
    'description' => 'reset users password by role name',
    'arguments' => [
      'role' => 'Roles id',
    ],
    'options' => [
      'skip-root' => 'Skip reset password for uid 1',
    ],
    'bootstrap' => DRUSH_BOOTSTRAP_DRUPAL_FULL,
    'drupal dependencies' => array(
      'dyniva_core'
    )
  );
  $items['enable-password-policy'] = array(
    'description' => 'Enable password policy',
    'bootstrap' => DRUSH_BOOTSTRAP_DRUPAL_FULL,
    'drupal dependencies' => array(
      'dyniva_core'
    )
  );
  
  return $items;
}

/**
 * Drush command to execute.
 */
function drush_dyniva_core_reset_password_by_role() {
  $roles = func_get_args();
  $skip_root = drush_get_option('skip-root');
  $query = \Drupal::database()->select('user__roles','r');
  $query->addField('r', 'entity_id');
  $query->condition('roles_target_id', $roles, 'IN');
  if($skip_root){
    $query->condition('entity_id', 1, '>');
  }
  $query->distinct();
  $uids = $query->execute()->fetchAll();
  foreach ($uids as $row){
    $password = user_password();
    $user = user_load($row->entity_id);
    if($user){
      $user->setPassword($password);
      $user->save();
      drush_log("Reset password : {$user->name->value} {$password}", 'ok');
    }
  }
}
/**
 * Drush command to execute.
 */
function drush_dyniva_core_enable_password_policy() {
  \Drupal::service('module_installer')->install(['password_policy','password_policy_character_types','password_policy_length']);
  if(!\Drupal::entityTypeManager()->getStorage('password_policy')->load('standard')){
    $values = [
      'id' => 'standard',
      'label' => 'Standard',
      'password_reset' => 0,
      'policy_constraints' => [
        [
          'id' => 'password_length',
          'character_length' => 8,
          'character_operation' => 'minimum',
        ],
        [
          'id' => 'character_types',
          'character_types' => 3,
        ],
      ],
      'roles' => [
        'editor' => 'editor',
        'manager' => 'manager',
        'webmaster' => 'webmaster',
        'authenticated' => 'authenticated',
        'administrator' => 'administrator',
      ]
    ];
    $policy = \Drupal::entityTypeManager()->getStorage('password_policy')->create($values);
    $policy->save();
  }
}
