<?php

namespace Drupal\dyniva_connect\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for Connector edit forms.
 *
 * @ingroup dyniva_connect
 */
class ConnectorForm extends ContentEntityForm {
  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    /* @var $entity \Drupal\dyniva_connect\Entity\Connector */
    $form = parent::buildForm($form, $form_state);
    $entity = $this->entity;
    
    $config = $entity->getConfigData();
    $type = $entity->getType();
    if($form_state->getValue('type')){
      $type = $form_state->getValue('type');
    }
    
    $manager = \Drupal::service('plugin.manager.connector_type_plugin');
    
    $form['type'] = array(
      '#type' => 'select',
      '#title' => $this->t('Connector type'),
      '#required' => true,
      '#options' => $manager->getSelectOptions(),
      '#disabled' => !$entity->isNew(),
      '#default_value' => $type,
      '#ajax' => [
        'callback' => '::connectorTypeCallback',
        'wrapper' => 'config-wrapper',
      ],
    );
    
    $form['config'] = array(
      '#type' => 'fieldset',
      '#title' => $this->t('Connector config'),
      '#tree' => true,
      '#prefix' => '<div id="config-wrapper">',
      '#suffix' => '</div>',
      'data' => array(),
    );
    
    $config_form = $manager->getConfigForm($type,$config);
    $form['config']['data'] = $config_form;
    
    return $form;
  }
  /**
   * Callback for both ajax-enabled buttons.
   *
   * Selects and returns the fieldset with the names in it.
   */
  public function connectorTypeCallback(array &$form, FormStateInterface $form_state) {
    $form_state->setRebuild(TRUE);
    return $form['config'];
  }
  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form,FormStateInterface $form_state){
    parent::validateForm($form, $form_state);
  }
  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $entity = $this->entity;
    $config = $form_state->getValue('config');
    $entity->setConfigData($config['data']);
    $status = parent::save($form, $form_state);

    switch ($status) {
      case SAVED_NEW:
        drupal_set_message($this->t('Created the %label Connector.', [
          '%label' => $entity->label(),
        ]));
        
        \Drupal::service("router.builder")->rebuild();
        break;

      default:
        drupal_set_message($this->t('Saved the %label Connector.', [
          '%label' => $entity->label(),
        ]));
    }
    $form_state->setRedirect('entity.connector.canonical', ['connector' => $entity->id()]);
  }

}
