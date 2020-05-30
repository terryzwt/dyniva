<?php

namespace Drupal\dyniva_prompt_message\Plugin\PromptRule;

use Drupal\dyniva_prompt_message\Plugin\PromptRulePluginBase;
use Drupal\dyniva_prompt_message\Form\PromptRuleForm;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\dyniva_prompt_message\Entity\PromptRule;
use Drupal\user\UserInterface;

/**
 * Entity CRUD Rule.
 *
 * @PromptRule(
 *  id = "user_login",
 *  label = @Translation("User Login")
 * )
 */
class UserLogin extends PromptRulePluginBase {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function getMessage(array $context = []) {
    $messages = [];
    if (!empty($context['entity'])) {
      /**
       * @var UserInterface $entity
       */
      $entity = $context['entity'];
      $rules = $this->getRules();
      foreach ($rules as $rule) {
        $params = $rule->getParams();
        $events = array_filter($params['events']);
        
        $flag = true;
        foreach ($events as $condition){
          if($condition == 'first_login' && $entity->getLastAccessedTime() != 0){
            $flag = false;
          }
          if($condition == 'has_role'){
            $roles = array_filter($params['roles']);
            $role_flag = false;
            foreach ($roles as $role_id){
              if($entity->hasRole($role_id)){
                $role_flag = true;
              }
            }
            if(!$role_flag){
              $flag = false;
            }
          }
          if($condition == 'incomplete_info'){
            $check_fields = array_filter($params['incomplete_check_fields']);
            $empty_flag = false;
            foreach ($check_fields as $field){
              if($entity->hasField($field) && $entity->{$field}->isEmpty()){
                $empty_flag = true;
              }
            }
            if(!$empty_flag){
              $flag = false;
            }
          }
          if($condition == 'prompt_once'){
            $state = \Drupal::state()->get('dyniva_prompt_message.user_login.prompt_once',[]);
            if(!empty($state[$entity->id()][$rule->id()])){
              $flag = false;
            }else{
              $state[$entity->id()][$rule->id()] = true;
              \Drupal::state()->set('dyniva_prompt_message.user_login.prompt_once',$state);
            }
          }
        }
        if($flag) {
          $messages[] = $rule;
        }
      }
    }
    return $messages;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigForm($config = []) {
    $form = [];

    $events_options = [
      'first_login' => $this->t('First Time Login'),
      'has_role' => $this->t('Has Roles'),
      'incomplete_info' => $this->t('Incomplete Information'),
      'prompt_once' => $this->t('Just prompt Once'),
    ];
    $form['events'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Conditions'),
      '#default_value' => $config['events']?:[],
      '#options' => $events_options,
      '#description' => $this->t('All checked conditions must satisfied to show the message.'),
    ];
    
    $roles = \Drupal::entityTypeManager()->getStorage('user_role')->loadMultiple();

    $roles_options = array_combine(array_keys($roles),array_keys($roles));
    
    $form['roles'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Has roles'),
      '#default_value' => $config['roles']?:[],
      '#options' => $roles_options,
      '#description' => $this->t('Any checked roles satisfies the condition.'),
      '#states' => [
        'visible' => [
          ':input[name="config[data][events][has_role]"]' => ['checked' => true]
        ]
      ]
    ];
    
    $fields = \Drupal::service('entity_field.manager')->getFieldDefinitions('user', 'user');

    $fields_options = array_combine(array_keys($fields),array_keys($fields));
    
    $form['incomplete_check_fields'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Incomplete Information check fields'),
      '#default_value' => $config['incomplete_check_fields']?:[],
      '#options' => $fields_options,
      '#description' => $this->t('Any checked field empty satisfies the condition.'),
      '#states' => [
        'visible' => [
          ':input[name="config[data][events][incomplete_info]"]' => ['checked' => true]
        ]
      ]
    ];
    
    return $form;
  }

}
