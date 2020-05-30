<?php

namespace Drupal\dyniva_content_moderation\Form;


use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\Entity\EntityFormDisplay;

class ContentModerationConfig extends FormBase {
  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'ccms_content_moderation_config';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    
    $types = $this->getConfigNodeTypes();
    $form['global'] = array(
      '#type' => 'fieldset',
      '#title' => $this->t('Global'),
      '#tree' => TRUE,
    );
    $node = \Drupal::entityManager()->getStorage('node')->create(['type' => 'landing_page']);
    $node->set('approvers', \Drupal::config('ccms.moderation')->get('approvers'));
    $pform = [];
    EntityFormDisplay::collectRenderDisplay($node, 'default')->buildForm($node, $pform, $form_state);
    $pform['approvers']['widget']['#parents'] = array('global','approvers');
    $form['global']['approvers'] = $pform['approvers'];
    
    foreach ($types as $type){
      $node_type = $type->id();
      $node = \Drupal::entityManager()->getStorage('node')->create(['type' => $node_type]);
      $node->set('approvers', \Drupal::config('node.type.' . $node_type)->get('third_party_settings.content_moderation.approvers'));
      $pform = [];
      EntityFormDisplay::collectRenderDisplay($node, 'default')->buildForm($node, $pform, $form_state);
      $form[$node_type] = array(
        '#type' => 'fieldset',
        '#title' => $type->label(),
        '#tree' => TRUE,
      );
      $pform['approvers']['widget']['#parents'] = array($node_type,'approvers');
      $form[$node_type]['approvers'] = $pform['approvers'];
    }
    $form_state->set('node_type', $types);
    $form['actions'] = ['#type' => 'container'];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => t('Save'),
      '#button_type' => 'primary',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    
    $config = \Drupal::service('config.factory')->getEditable('ccms.moderation');
    $config->set('approvers', array_filter(array_column($values['global']['approvers'], 'target_id')))->save();
    
    foreach ($form_state->get('node_type') as $type){
      $node_type = $type->id();
      $config = \Drupal::service('config.factory')->getEditable('node.type.' . $node_type);
      $config->set('third_party_settings.content_moderation.approvers', array_filter(array_column($values[$node_type]['approvers'], 'target_id')))->save();
    }
  }

  private function getConfigNodeTypes(){
    $types = array();
    $bundles = node_type_get_types();
    $moderation = \Drupal::service('content_moderation.moderation_information');
    $node_type = \Drupal::entityTypeManager()->getDefinition('node');
    foreach ($bundles as $item){
      if($moderation->shouldModerateEntitiesOfBundle($node_type, $item->id())){
        $types[] = $item;
      }
    }
    return $types;
  }
}
