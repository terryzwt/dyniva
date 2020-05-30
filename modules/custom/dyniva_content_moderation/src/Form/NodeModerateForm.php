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
use Drupal\Core\Url;
use Drupal\content_moderation\Entity\ModerationStateTransition;
use Drupal\content_moderation\Entity\ModerationState;
use Drupal\message\Entity\Message;
use Drupal\Component\Serialization\Json;
use Drupal\Component\Diff\Diff;
use Drupal\Component\Diff\DiffFormatter;
use Drupal\Component\Utility\DiffArray;
use Drupal\workflows\WorkflowInterface;
use Drupal\workflows\Transition;
use Drupal\content_moderation\ModerationInformation;

/**
 * The EntityModerationForm provides a simple UI for changing moderation state.
 */
class NodeModerateForm extends FormBase implements ContainerInjectionInterface {

  /**
   * The node.
   *
   * @var \Drupal\node\NodeInterface
   */
  protected $node;

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
    return 'ccms_node_moderate_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, NodeInterface $node = NULL,$default_state = NULL) {
    $this->node = $node;
    if(empty($node)){
      return [];
    }
    $node_type = $node->getType();
    
    /**
     * @var ModerationInformation $moderation_info
     */
    $moderation_info = \Drupal::service('content_moderation.moderation_information');
    
    if(!$moderation_info->isModeratedEntity($node)){
      return [];
    }
    

    $form['#theme'] = 'node_moderate_form';
    $moderation_state = $node->moderation_state->value;
    /**
     *
     * @var WorkflowInterface $workflow
     */
    $workflow = $moderation_info->getWorkflowForEntity($node);
    if (!$moderation_state && $workflow) {
      $moderation_state = $workflow->getTypePlugin()->getInitialState($node)->id();
    }
    $current_state = $workflow->getTypePlugin()->getState($moderation_state);
    $form['state'] = [
      '#markup' => '<h3>' . t('Current State') . ': ' . $current_state->label() . '</h3>',
    ];


    $node_storage = \Drupal::entityManager()->getStorage('node');
    $revision_ids = workspace_ccms_entity_revisionIds($node);
    $change_items = [];
    $cur_vid = $last_vid = reset($revision_ids);
    foreach ($revision_ids as $index => $revision_id) {
      /**
       *
       * @var NodeInterface $revision
       */
      $revision = $node_storage->loadRevision($revision_id);
      $log_rows = [['#markup' => $revision->revision_log->value]];
      if ($revision->isPublished())
        break;
      if (isset($revision_ids[$index + 1])) {
        $last_vid = $revision_ids[$index + 1];
        $prevision = $node_storage->loadRevision($revision_ids[$index + 1]);
        $log_rows = $this->getRevisionChanges($revision, $prevision);
      }
      $username = [
        '#theme' => 'username',
        '#account' => $revision->getRevisionUser(),
      ];
      $row = [
        'vid' => $revision->getRevisionId(),
        'date' => $revision->getChangedTime(),
        'user' => $username,
        'comments' => $log_rows,
        'state' => $workflow->getTypePlugin()->getState($revision->moderation_state->value)->label(),
      ];
      $change_items[$revision->getChangedTime()] = $row;
    }

    /**
     * @var DateFormatter $dateformatter
     */
    $dateformatter = \Drupal::service('date.formatter');

    krsort($change_items);
    $items = array();
    // $changes_markup = '<div class="list-panel"><h4>Changes</h4><ul>';
    foreach ($change_items as $timestamp => $row){
      $day = $dateformatter->format($timestamp, 'custom','F j, Y');
      $row['date'] = $dateformatter->formatInterval(time() - $row['date']) . ' ago';
      $items[$day][] = $row;
      // $changes_markup .= '<li>';
      // $changes_markup .= '';
      // $changes_markup .= '</li>';
    }
    if ($items) {
      $form['changes'] = [
        '#theme' => 'node_moderate_changes',
        // '#title' => t('Changes'),
        '#items' => $items,
        '#prefix' => '<div class="list-panel ccms-margin-bottom"><h4>'.t('Changes').'</h4>',
        '#suffix' => '</div>',
      ];
    }

    if($node_type != 'json_content'){
      $form['last_link'] = [
        '#markup' => Url::fromRoute('entity.node.revision', ['node' => $node->id(), 'node_revision' => $last_vid])->toString(),
      ];
      $form['current_link'] = [
        '#markup' => $node->toUrl()->toString(),
      ];
    }


    $transition_validation = \Drupal::service('content_moderation.state_transition_validation');
    $transitions = $transition_validation->getValidTransitions($node, $this->currentUser());
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
      // '#title' => t('Change State'),
      '#options' => $target_states,
      '#default_value' => $default_state?:end($state_keys),
      '#prefix' => '<div class="list-panel"><h4>'.t('Change State').'</h4>',
      '#suffix' => '</div>',
    ];
    $form['comment'] = [
      '#type' => 'textfield',
      '#placeholder' => t('Log message'),
    ];

    $form['actions'] = [
      '#type' => 'container'
    ];
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
    $old_state = $this->node->moderation_state->value;
    $new_state = $form_state->getValue('transition_target');

    /**
     *
     * @var WorkflowInterface $workflow
     */
    $workflow = \Drupal::service('content_moderation.moderation_information')->getWorkflowForEntity($this->node);
    $state = $workflow->getTypePlugin()->getState($new_state);
    $comment = $form_state->getValue('comment')?:t('Update status to ') . $state->label();

    $this->node->original = clone $this->node;
    $this->node->moderation_state->value = $new_state;
    $this->node->revision_log = $comment;
    $this->node->setRevisionTranslationAffected(TRUE);
    $this->node->save();

    drupal_set_message(t('Successfully updated.'), 'status');

//     $message = Message::create(['template' => 'content_moderation', 'uid' => \Drupal::currentUser()->id()]);
//     $transition = $workflow->getTransitionFromStateToState($old_state, $new_state);
//     $message->set('transition', $transition->id());
//     $message->set('content_reference', $this->node);
//     $message->set('comment', $form_state->getValue('comment'));
//     $message->save();
  }

  protected function getLastPublished(NodeInterface $node = NULL) {
    $node_storage = \Drupal::entityManager()->getStorage('node');
    $revision_ids = array_reverse($node_storage->revisionIds($node));
    foreach ($revision_ids as $revision_id) {
      $revision = $node_storage->loadRevision($revision_id);
      /**
       *
       * @var WorkflowInterface $workflow
       */
      $workflow = \Drupal::service('content_moderation.moderation_information')->getWorkflowForEntity($revision);
      $state = $workflow->getTypePlugin()->getState($revision->moderation_state->value);
      if ($state->isPublishedState()) {
        return $revision;
      }
    }
  }

  public static function getRevisionChanges(NodeInterface $revision, NodeInterface $prevision = NULL) {
    $changes = [];
    $type = $revision->getType();
    if($type != 'json_content'){
      $block_content_storage = \Drupal::entityManager()->getStorage('block_content');
      if (isset($revision->panelizer[0]->panels_display['blocks'])) {
        foreach ($revision->panelizer[0]->panels_display['blocks'] as $block_uuid => $block) {
          if (isset($prevision->panelizer[0]->panels_display['blocks'][$block_uuid]) && ($pblock = $prevision->panelizer[0]->panels_display['blocks'][$block_uuid])) {
            if ($block['region'] != $pblock['region']) {
              $changes[] = t('%block region %from to %to', ['%block' => $block['label'],
                '%from' => $pblock['region'],
                '%to' => $block['region']]);
            }
            if ($block['weight'] != $pblock['weight']) {
              $changes[] = t('Moved %block: weight %from to %to', ['%block' => $block['label'],
                '%from' => $pblock['weight'],
                '%to' => $block['weight']]);
            }
            if (isset($pblock['vid']) && $block['vid'] != $pblock['vid']) {
              $block_content_revision = $block_content_storage->loadRevision($block['vid']);
              if ($block_content_revision->revision_log->value) {
                $changes[] = t('Updated %block: %revision_log', [
                  '%block' => $block['label'],
                  '%revision_log' => $block_content_revision->revision_log->value]);
              }
            }
          }
          else {
            if ($block['provider'] == 'block_content') {
              $block_content_revision = $block_content_storage->loadRevision($block['vid']);
              $changes[] = t('Added %block: %revision_log', [
                '%block' => $block['label'],
                '%revision_log' => $block_content_revision->revision_log->value]);
            }
            else {
              $changes[] = t('%block added', ['%block' => $block['label']]);
            }
          }
        }
      }

      if (isset($prevision->panelizer[0]->panels_display['blocks'])) {
        foreach ($prevision->panelizer[0]->panels_display['blocks'] as $block_uuid => $pblock) {
          if (isset($revision->panelizer[0]->panels_display['blocks'][$block_uuid]) && !$block = $revision->panelizer[0]->panels_display['blocks'][$block_uuid]) {
            $changes[] = t('%block deleted', ['%block' => $pblock['label']]);
          }
        }
      }
    }else{
//       $json = Json::decode($revision->json_content->value);
//       $json_pre = Json::decode($prevision->json_content->value);
//       if($json && $json_pre){
//         $diff = new Diff([$prevision->json_content->value],[$revision->json_content->value]);
//         $formatter = new DiffFormatter();
//         $changes = explode("\n", $formatter->format($diff));
//       }
    }

    if(!empty($revision->getRevisionLogMessage())){
      $changes[] = $revision->getRevisionLogMessage();
    }
    return $changes;
  }

}
