<?php

namespace Drupal\dyniva_content_moderation\Plugin\Action;

/**
 * Moderate Send For Approve.
 *
 * @Action(
 *   id = "moderate_send_for_approve_action",
 *   label = @Translation("Send for approve")
 * )
 */
class SendForApprove extends ModerateBase {

  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, 'draft', 'need_approve');
  }
}
