<?php

namespace Drupal\dyniva_content_moderation\Form;


use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\Entity\EntityFormDisplay;

class ModerationConfig extends FormBase {
  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'ccms_moderation_config';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $node = \Drupal::entityManager()->getStorage('node')->create(['type' => 'landing_page']);
    $node->set('approvers', \Drupal::config('ccms.moderation')->get('approvers'));
    $pform = [];
    EntityFormDisplay::collectRenderDisplay($node, 'default')->buildForm($node, $pform, $form_state);
    $form['approvers'] = $pform['approvers'];
    $form['approvers']['#weight'] = 0;
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
    $config = \Drupal::service('config.factory')->getEditable('ccms.moderation');
    $config->set('approvers', array_filter(array_column($form_state->getValue('approvers'), 'target_id')))->save();
  }

}
