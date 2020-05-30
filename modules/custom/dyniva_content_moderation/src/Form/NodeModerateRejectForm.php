<?php

namespace Drupal\dyniva_content_moderation\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\message\Entity\Message;

class NodeModerateRejectForm extends FormBase {
  /**
   * The revision.
   *
   * @var \Drupal\node\NodeInterface
   */
  protected $revision;

  /**
   * The user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $user;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'ccms_node_moderate_reject_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $node_revision = NULL, $uid = 0, $timestamp = 0, $hash = 0) {
    $form['#revision'] = \Drupal::entityManager()->getStorage('node')->loadRevision($node_revision);
    $form['#user'] = \Drupal::entityManager()->getStorage('user')->load($uid);
    $form['comment'] = [
      '#type' => 'textfield',
      '#placeholder' => t('Reason for rejection'),
    ];

    $form['actions'] = ['#type' => 'container'];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => t('Submit'),
      '#button_type' => 'primary',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    
    $revision = $form['#revision'];
    $user = $form['#user'];
    
    user_login_finalize($user);
    
    $approval_storage = \Drupal::entityManager()->getStorage('approval');
    $approval_values = [
        'entity_id' => $revision->id(),
        'entity_revision_id' => $revision->vid->value,
        'entity_type' => 'node',
        'type' => 'approval',
        'created' => REQUEST_TIME,
        'status' => 0,
        'comment' => $form_state->getValue('comment'),
        'uid' => $user->id(),
    ];
    $approval_storage->create($approval_values)->save();

    $revision->moderation_state->value = 'draft';
    $revision->save();
    
    $params = ['node' => $revision, 'user' => $user,'action' => 'rejected','workspace_id' => \Drupal::service('workspace.manager')->getActiveWorkspace()->id()];
    $editors = ccms_user_load_user_by_role('content_editor');
    if(ccms_content_is_email_notify_enable()){
      foreach ($editors as $editor){
        $langcode = $editor->getPreferredLangcode();
        \Drupal::service('plugin.manager.mail')->mail('ccms_content', 'content_status', $editor->mail->value, $langcode, $params);
      }
    }
    /**
     *
     * @var WorkflowInterface $workflow
     */
    $workflow = \Drupal::service('content_moderation.moderation_information')->getWorkflowForEntity($revision);
    
    $message = Message::create(['template' => 'content_moderation', 'uid' => \Drupal::currentUser()->id()]);
    $transition = $workflow->getTransitionFromStateToState('need_approve', 'draft');
    $message->set('transition', $transition->id());
    $message->set('content_reference', $revision);
    $message->set('comment', '');
    $message->save();
    
    $managedEntity = dyniva_core_get_entity_managed_entity($revision);
    $form_state->setRedirect("dyniva_core.managed_entity.{$managedEntity->id()}.moderation_page", ['managed_entity' => $managedEntity->id(),'managed_entity_id' => $revision->id()]);
  }
}
