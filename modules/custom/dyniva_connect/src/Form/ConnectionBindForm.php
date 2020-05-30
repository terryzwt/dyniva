<?php

namespace Drupal\dyniva_connect\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\dyniva_connect\Entity\Connector;
use Drupal\dyniva_connect\Entity\Connection;
use Drupal\Core\Url;

/**
 * Class ConnectionBindForm.
 *
 * @package Drupal\dyniva_connect\Form
 */
class ConnectionBindForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'connection_bind_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state,Connector $connector = null,Connection $connection = null) {

    $storage = &$form_state->getStorage();
    $storage['connector'] = $connector;
    $storage['connection'] = $connection;
    
    $manager = \Drupal::service('plugin.manager.connector_type_plugin');
    $bind_form = $manager->buildBindForm($connector,$connection);
    $form += $bind_form;
    
    $form['#prefix'] = '<div id="user-bind-form">';
    $form['#suffix'] = '</div>';
    
    $form['actions'] = [
      '#type' => 'actions',
    ];
    
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Bind'),
//       '#ajax' => array(
//         'callback' => '::bindValidate',
//         'wrapper' => 'user-bind-form',
//       )
    ];
    $form['#cache'] = array(
      'max-age' => 0
    );
    return $form;
  }

  
  public function bindValidate(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $storage = $form_state->getStorage();
    $connector = $storage['connector'];
    $manager = \Drupal::service('plugin.manager.connector_type_plugin');
    $user = $manager->getBindUser($connector,$values);
    if($user){
      $connection = $storage['connection'];
      $connection->setOwner($user);
      $connection->save();
    }else{
      $form_state->setErrorByName('actions',$this->t('No match user.'));
    }
    return $form;
  }
  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $storage = $form_state->getStorage();
    $connector = $storage['connector'];
    $manager = \Drupal::service('plugin.manager.connector_type_plugin');
    $user = $manager->getBindUser($connector,$values);
    if($user){
      $connection = $storage['connection'];
      $connection->setOwner($user);
      $connection->save();
    }else{
      $form_state->setErrorByName('actions',$this->t('No match user.'));
    }
  }
  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $storage = $form_state->getStorage();
    $connection = $storage['connection'];
    $account = $connection->getOwner();
    if($account){
      user_login_finalize($account);
    }
    drupal_set_message($this->t('Bind successfuly.'));
    $form_state->setRedirectUrl(Url::fromUserInput("/"));
  }

}
