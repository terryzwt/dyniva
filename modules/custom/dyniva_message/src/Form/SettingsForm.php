<?php

namespace Drupal\dyniva_message\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

class SettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'dyniva_admin_message_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['dyniva_message.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $config = $this->configFactory->get('dyniva_message.settings');

    $storage = \Drupal::service('entity.manager')->getStorage('message_template');
    $rows = $storage->getQuery()->execute();
    
    $form['subscribe_message_template'] = [
      '#type' => 'select',
      '#title' => 'Subscribe message template',
      '#multiple' => TRUE,
      '#options' => $rows,
      '#required' => true,
      '#default_value' => $config->get('subscribe_message_template')?$config->get('subscribe_message_template'):[],
      '#description' => '可订阅message类型，当用户订阅了可订阅的message类型（或是个人消息），此类message被创建时将会主动通知订阅用户。通知方式取决于Default message notifiers。'
    ];

    $form['node_notification'] = [
      '#type' => 'checkbox',
      '#title' => "Node notification",
      '#default_value' => 1,
      '#disabled' => true,
      '#description' => '启用此项，node的创建/更新/删除将会创建message'
    ];
    
    $form['content_moderation_notification'] = [
      '#type' => 'checkbox',
      '#title' => "Content moderation notification",
      '#default_value' => 1,
      '#disabled' => true,
      '#description' => '启用此项，node更新触发的workflow将会创建message'
    ];

    $type = \Drupal::service('plugin.message_notify.notifier.manager');
    $plugin_definitions = $type->getDefinitions();
    $options = [];
    foreach($plugin_definitions as $key => $plugin) {
      $options[$key] = $plugin['title'];
    }
    
    $form['force_notifiers'] = [
      '#type' => 'select',
      '#title' => 'Default notifiers',
      '#multiple' => TRUE,
      '#options' => $options,
      '#required' => true,
      '#default_value' => $config->get('force_notifiers')?$config->get('force_notifiers'):[],
      '#description' => 'Site wide default notifiers, will be overwrite by user settings.'
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $config = $this->configFactory->getEditable('dyniva_message.settings');
    $config->set('subscribe_message_template', $form_state->getValue('subscribe_message_template'));
    $config->set('force_notifiers', $form_state->getValue('force_notifiers'));
    $config->save();

    parent::submitForm($form, $form_state);
  }

}
