<?php

namespace Drupal\dyniva_message\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

class UserForm extends FormBase {
  
  protected  $user = NULL;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'dyniva_message_user_form';
  }

  private function getDisplayForm() {
    return \Drupal::entityTypeManager()
      ->getStorage('entity_form_display')
      ->load('user.user.message');
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $user = Null) {
    $this->user = $user;
    $default_notifiers = [];
    $config = \Drupal::service('config.factory')->get('dyniva_message.settings');
    if(is_array($config->get('force_notifiers'))) {
      $default_notifiers = $config->get('force_notifiers');
    }

    $notifier_manager = \Drupal::service('plugin.message_notify.notifier.manager');
    $plugin_definitions = $notifier_manager->getDefinitions();
    if($user->hasField('notifiers')) {
      if(!$user->notifiers->isEmpty()){
        $default_notifiers = array_column($user->notifiers->getValue(), 'value');
      }
      $form['group_notifier'] = [
        '#type' => 'fieldset',
        '#title' => $this->t('Notifier'),
        '#description' => $this->t('Please select your message notifiers.'),
        '#tree' => true,
      ];
      foreach($plugin_definitions as $key => $plugin) {
        if($key == 'dyniva_log' && !\Drupal::currentUser()->hasPermission('administer messages')) {
          continue;
        }
        $form['group_notifier'][$key] = [
          '#type' => 'checkbox',
          '#title' => $plugin['title'],
          '#description' => $plugin['description'],
          '#default_value' => in_array($key, $default_notifiers),
        ];
      }
    
      // 浏览器通知设置
      $form['group_notifier']['browser_notification'] = [
        '#type' => 'checkbox',
        '#title' => $this->t("Browser notification"),
        '#default_value' => in_array('browser_notification', $default_notifiers),
        '#description' => $this->t('Show messages in browser notification or alert dialog.')
      ];
    }
    
    $form['actions'] = [
      '#type' => 'actions',
    ];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // $this->getDisplayForm()->validateFormValues($this->getEntity(), $form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $user = $this->user;
    if($values = $form_state->getValue('group_notifier')){
      $user->notifiers = array_keys(array_filter($values));
      $user->save();
    }
    drupal_set_message($this->t('Notifier saved successfuly.'),'status');
  }

  public function getEntity() {
    $user = \Drupal::routeMatch()->getParameter('managed_entity_id');
    if($user) {
      return $user;
    }
    return user_load(\Drupal::currentUser()->id());
  }

  private function any($items, $func)
  {
    return count(array_filter($items, $func)) > 0;
  }

}
