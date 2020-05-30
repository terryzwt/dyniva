<?php

namespace Drupal\dyniva_content_moderation\Form;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\node\NodeInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\content_moderation\Entity\ModerationStateTransition;
use Drupal\message\Entity\Message;
use Drupal\workflows\Transition;
use Drupal\Core\Entity\EntityPublishedInterface;

/**
 * The EntityModerationForm provides a simple UI for changing moderation state.
 */
class EntityModerateForm extends FormBase implements ContainerInjectionInterface {

  /**
   * @var EntityInterface
   */
  protected $entity;

  /**
   * The renderer service.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * Constructs a RevisionsController object.
   *
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   *   The date formatter service.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer service.
   */
  public function __construct(RendererInterface $renderer) {
    $this->renderer = $renderer;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
        $container->get('renderer')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'ccms_entity_moderate_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, EntityInterface $entity = NULL) {
    $this->entity = $entity;

    $form['#theme'] = 'entity_moderate_form';
    
    if(!isset($entity->moderation_state->value)){
      return array();
    }
    /**
     *
     * @var WorkflowInterface $workflow
     */
    $workflow = \Drupal::service('content_moderation.moderation_information')->getWorkflowForEntity($this->entity);
    $current_state = $workflow->getTypePlugin()->getState($entity->moderation_state->value);
    
    $form['state'] = [
      '#markup' => '<h2>' . t('Current State') . ': ' . $current_state->label() . '</h2>',
    ];

    if($entity->getEntityTypeId() == 'media' && $entity->bundle() == 'image'){
      $query = \Drupal::database()->select('media_revision','t');
      $query->addField('t', 'vid');
      $query->condition('mid',$entity->id());
      $query->orderBy('vid','DESC');
      $query->range(1,1);
      $last_vid = $query->execute()->fetchField();
      if($last_vid){
        
        $last = \Drupal::entityTypeManager()->getStorage('media')->loadRevision($last_vid);
        $last_url = file_create_url($last->image->entity->uri->value);
        $form['last_link'] = [
          '#markup' => $last_url,
        ];
        $form['last_img'] = [
          '#markup' => $last_url,
        ];
        $current_url = file_create_url($entity->image->entity->uri->value);
        $form['current_link'] = [
          '#markup' => $current_url,
        ];
        $form['current_img'] = [
          '#markup' => $current_url,
        ];
      }
    }
    

    if ($entity->moderation_state->value == 'need_approve' && isset($moderations[$this->currentUser()->id()])) {
      return $form;
    }
    
    $transition_validation = \Drupal::service('content_moderation.state_transition_validation');
    $transitions = $transition_validation->getValidTransitions($entity, $this->currentUser());
    // Exclude self-transitions.
    $transitions = array_filter($transitions, function(Transition $transition) use ($current_state) {
      return $transition->to()->id() != $current_state->id();
    });
    $target_states = [];
    /** @var ModerationStateTransition $transition */
    foreach ($transitions as $transition) {
      $target_states[$transition->to()->id()] = $transition->label();
    }
        
    if (!count($target_states)) {
      return $form;
    }
    $state_keys = array_keys($target_states);
    $form['transition_target'] = [
      '#type' => 'radios',
      '#title' => t('Change State'),
      '#options' => $target_states,
      '#default_value' => end($state_keys),
    ];
    $form['comment'] = [
      '#type' => 'textfield',
      '#placeholder' => t('Log message'),
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
    $account = $this->currentUser();
    $old_state = $this->entity->moderation_state->value;
    $new_state = $form_state->getValue('transition_target');
    /**
     *
     * @var WorkflowInterface $workflow
     */
    $workflow = \Drupal::service('content_moderation.moderation_information')->getWorkflowForEntity($this->entity);
    $state = $workflow->getTypePlugin()->getState($new_state);
    $comment = $form_state->getValue('comment')?:t('Update status to') . $state->label();
    
    if ($new_state == 'need_approve') {
      $this->entity->moderation_state->value = $new_state;
      $this->entity->revision_log = $comment;
      $this->entity->save();
      

      if(!empty($this->entity->approvers)){
        $site_mail = \Drupal::config('system.site')->get('mail_notification');
        if (empty($site_mail)) {
          $site_mail = \Drupal::config('system.site')->get('mail');
        }
        if (empty($site_mail)) {
          $site_mail = ini_get('sendmail_from');
        }
        foreach ($this->entity->approvers as $approver) {
          $langcode = $approver->entity->getPreferredLangcode();
//           \Drupal::service('plugin.manager.mail')->mail('dyniva_content_moderation', 'moderate', $approver->entity->mail->value, $langcode, ['entity' => $this->entity, 'approver' => $approver->entity], $site_mail);
        }
      }
    }else{
      $this->entity->moderation_state->value = $new_state;
      $this->entity->setRevisionLogMessage($comment);
      $this->entity->save();
    }
    if($this->entity->getEntityTypeId() == 'deployment_entity'){
      $message = Message::create(['template' => 'deploy_moderation', 'uid' => \Drupal::currentUser()->id()]);
      $transition = $workflow->getTransitionFromStateToState($old_state, $new_state);
      $message->set('transition', $transition->id());
      $message->set('deploy_reference', $this->entity);
      $message->set('comment', $form_state->getValue('comment'));
      $message->save();
    }
  }
}
