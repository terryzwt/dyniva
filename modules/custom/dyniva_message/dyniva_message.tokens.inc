<?php

/**
 * @file
 * Builds placeholder replacement tokens for message-related data.
 */

use Drupal\Core\Render\BubbleableMetadata;
use Drupal\Component\Serialization\PhpSerialize;

/**
 * Implements hook_token_info().
 */
function dyniva_message_token_info() {
  $type = [
    'name' => t('Dyniva messages'),
    'description' => t('Tokens related to individual content items, or "messages".'),
    'needs-data' => 'message',
  ];

  $message['moderation'] = [
    'name' => t("Moderation actions"),
  ];

  $message['account_user_roles'] = [
    'name' => t("Message account field user role names"),
  ];

  $message['modified_fields'] = [
    'name' => t('Modified fields')
  ];

  return [
    'types' => ['dyniva_message' => $type],
    'tokens' => ['dyniva_message' => $message],
  ];
}

/**
 * Implements hook_tokens().
 */
function dyniva_message_tokens($type, $tokens, array $data, array $options, BubbleableMetadata $bubbleable_metadata) {
  $token_service = \Drupal::token();

  $replacements = [];

  if ($type == 'dyniva_message' && !empty($data['message'])) {
    /** @var \Drupal\message\Entity\Message $message */
    $message = $data['message'];

    foreach ($tokens as $name => $original) {
      switch ($name) {
        case 'moderation':
          $out = "";
          if (!empty($message->transition->value) && !empty($message->content_id->value)) {
            $moderation_info = \Drupal::service('content_moderation.moderation_information');
            $content = \Drupal::entityTypeManager()->getStorage('node')->load($message->content_id->value);
            if ($moderation_info->isModeratedEntity($content)) {
              $workflow = $moderation_info->getWorkflowForEntity($content);
              if ($workflow) {
                $transition = $workflow->getTypePlugin()->getTransition($message->transition->value);
                $out = t($transition->label());
              }
            }
          }
          $replacements[$original] = $out;
          break;

        case 'account_user_roles':
          $user = $message->account->entity;
          $out = "";
          if ($user) {
            $roles = $user->getRoles(TRUE);
            $labels = [];
            foreach ($roles as $rid) {
              $role = user_role_load($rid);
              $labels[] = $role->label();
            }
            $out = implode(',', $labels);
          }
          $replacements[$original] = $out;
          break;
        
        case 'modified_fields':
          $skipped = [
            'created', 'changed', 'vid', 'revision_uid', 'uid', 'revision_default',
            'revision_translation_affected', 'moderation_state', 'metatag', 'revision_token',
            'content_translation_source', 'content_translation_outdated', 'workspace', 'path',
            '_rev', '_deleted', 'panelizer', 'type', 'revision_timestamp', 'status', 'revision_log'
          ];
          if(!empty($message->content_id->value) && $message->hasField('content_vid') && !$message->content_vid->isEmpty()) {
            $nodeStorage = \Drupal::entityTypeManager()->getStorage('node');
            $node = $nodeStorage->load($message->content_id->value);
            $vids = $nodeStorage->revisionIds($node);
            if(count($vids) > 0) {
              $default_vid = $message->content_vid->value;
              $index = array_search($default_vid, $vids) - 1;
              $vid = $vids[$index];
              if($vid >= 0) {
                $modifiedFields = [];
                $prevNode = $nodeStorage->loadRevision($vid);
                $fields = $prevNode->getFields();
                foreach($fields as $fieldName => $field) {
                  if(in_array($fieldName, $skipped)) continue;
                  $a = PhpSerialize::encode($node->get($fieldName)->getValue());
                  $b = PhpSerialize::encode($field->getValue());
                  if($a != $b) {
                    $modifiedFields[]= $field->getFieldDefinition()->getLabel();
                  }
                }
                if($modifiedFields) {
                  $replacements[$original] = implode(', ', $modifiedFields);
                  break;
                }
              }
            }
          }
          $replacements[$original] = '';
          break;
      }
    }
  }

  return $replacements;
}
