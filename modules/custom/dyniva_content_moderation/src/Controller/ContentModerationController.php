<?php

namespace Drupal\dyniva_content_moderation\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\node\NodeInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Component\Utility\Crypt;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\message\Entity\Message;
use Drupal\dyniva_content_moderation\Form\NodeModerateForm;
use Drupal\Core\Url;

class ContentModerationController extends ControllerBase {

  public function accessModerate(NodeInterface $node = NULL) {
    // debug 閺嗗倹妞傛径鍥ㄦ暈閿涘苯鍘戠拋鍛婂閺堝顫楅懝鏌ュ厴閸欘垯浜掔拋鍧楁６
    return AccessResult::allowed()->setCacheMaxAge(0);

    if (\Drupal::config('node.type.' . $node->bundle())->get('third_party_settings.content_moderation')) {
      $transition_validation = \Drupal::service('content_moderation.state_transition_validation');
      if ($valid_transition_targets = $transition_validation->getValidTransitionTargets($node, $this->currentUser())) {
        if ($node->moderation_state && $node->moderation_state->value == 'need_approve') {
          if (!in_array($this->currentUser()->id(), array_column($node->approvers->getValue(), 'target_id'))) {
            return AccessResult::forbidden()->setCacheMaxAge(0);
          }
        }
        return AccessResult::allowed()->setCacheMaxAge(0);
      }
    }

    return AccessResult::forbidden()->setCacheMaxAge(0);
  }

  public function accessModerateAction($node_revision = null, $uid, $timestamp, $hash) {
//     $revision = $this->entityManager()->getStorage('node')->loadRevision($node_revision);

    $account = \Drupal::currentUser();
    $user = $this->entityManager()->getStorage('user')->load($uid);

    if($account->id() == $user->id()){
      return AccessResult::allowed()->setCacheMaxAge(0);
    }

    if ($user === NULL || !Crypt::hashEquals($hash, user_pass_rehash($user, $timestamp))) {
      return AccessResult::forbidden()->setCacheMaxAge(0);
    }

    user_login_finalize($user);

    return AccessResult::allowed()->setCacheMaxAge(0);
  }

  public function preview($node_revision = null, $uid, $timestamp, $hash) {
    $revision = $this->entityManager()->getStorage('node')->loadRevision($node_revision);
    /**
     * @var \Drupal\Core\Url $link
     */
    $link = $revision->toUrl()->setAbsolute();
    $link->setOption('query', ['workspace_id' => $_GET['workspace_id']]);
    return $this->redirect($link->getRouteName(), $link->getRouteParameters(),$link->getOptions());
  }

  public function redirectTo($node_revision = null, $uid, $timestamp, $hash) {
    $redirect = \Drupal::destination()->get();
    $response = new RedirectResponse($redirect);
    return $response;
  }

  public function reject($node_revision = null, $uid, $timestamp, $hash) {
    $revision = $this->entityManager()->getStorage('node')->loadRevision($node_revision);
    $user = $this->entityManager()->getStorage('user')->load($uid);


    $form = \Drupal::formBuilder()->getForm(NodeModerateForm::class,$revision,'draft');

    return $form;
  }
  public function approve($node_revision = null, $uid, $timestamp, $hash) {
    $revision = $this->entityManager()->getStorage('node')->loadRevision($node_revision);
    $user = $this->entityManager()->getStorage('user')->load($uid);

    user_login_finalize($user);

    $moderations = dyniva_content_moderation_get_entitiy_approval($revision);
    $moderations = array_filter($moderations,function($v){return $v;});

    $comment = 'Approved';
    if(!isset($moderations[$uid])){
      $approval_storage = $this->entityManager()->getStorage('approval');
      $approval_values = [
        'entity_id' => $revision->id(),
        'entity_revision_id' => $revision->vid->value,
        'entity_type' => 'node',
        'type' => 'approval',
        'created' => REQUEST_TIME,
        'status' => 1,
        'comment' => $comment,
        'uid' => $user->id(),
      ];
      $approval_storage->create($approval_values)->save();

      $moderations[$uid] = 1;

      $params = ['node' => $revision, 'user' => $user,'action' => 'approved','workspace_id' => \Drupal::service('workspace.manager')->getActiveWorkspace()->id()];
      $editors = ccms_user_load_user_by_role('content_editor');
      if(ccms_content_is_email_notify_enable()){
        foreach ($editors as $editor){
          $langcode = $editor->getPreferredLangcode();
          \Drupal::service('plugin.manager.mail')->mail('ccms_content', 'content_status', $editor->mail->value, $langcode, $params);
        }
      }

      $message = Message::create(['template' => 'content_moderation', 'uid' => \Drupal::currentUser()->id()]);
      $transition = dyniva_content_moderation_get_transition_from_states('need_approve', 'pending_publish');
      $message->set('moderation_transition', $transition);
      $message->set('content_reference', $revision);
      $message->set('comment', '');
      $message->save();
    }

    if (empty(array_diff(array_column($revision->approvers->getValue(), 'target_id'),array_keys($moderations)))) {
      $revision->moderation_state->value = 'pending_publish';
      $revision->save();
    }

    $managedEntity = dyniva_core_get_entity_managed_entity($revision);
    drupal_set_message(t('Approved successfully.'), 'status');
    return new RedirectResponse($this->Url("dyniva_core.managed_entity.{$managedEntity->id()}.moderation_page", ['managed_entity' => $managedEntity->id(),'managed_entity_id' => $revision->id()]));
  }
}
