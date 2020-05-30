<?php

namespace Drupal\dyniva_prompt_message\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;

/**
 * Defines the Managed entity entity.
 *
 * @ConfigEntityType(
 * id = "prompt_rule",
 * label = @Translation("Prompt Rule"),
 * handlers = {
 * "list_builder" = "Drupal\dyniva_prompt_message\PromptRuleListBuilder",
 * "form" = {
 * "add" = "Drupal\dyniva_prompt_message\Form\PromptRuleForm",
 * "edit" = "Drupal\dyniva_prompt_message\Form\PromptRuleForm",
 * "delete" = "Drupal\dyniva_prompt_message\Form\PromptRuleDeleteForm"
 * },
 * "route_provider" = {
 * "html" = "Drupal\dyniva_prompt_message\PromptRuleHtmlRouteProvider",
 * },
 * },
 * config_prefix = "prompt_rule",
 * admin_permission = "administer site configuration",
 * entity_keys = {
 * "id" = "id",
 * "label" = "label",
 * "uuid" = "uuid"
 * },
 * links = {
 * "canonical" = "/manage/prompt-rule/{prompt_rule}",
 * "add-form" = "/manage/prompt-rule/add",
 * "edit-form" = "/manage/prompt-rule/{prompt_rule}/edit",
 * "delete-form" = "/manage/prompt-rule/{prompt_rule}/delete",
 * "collection" = "/manage/prompt-rule"
 * }
 * )
 */
class PromptRule extends ConfigEntityBase {
  /**
   * The Managed entity ID, used in url.
   *
   * @var string
   */
  protected $id;

  /**
   * The Managed entity label.
   *
   * @var string
   */
  protected $label;

  /**
   * The Rule type.
   *
   * @var string
   */
  protected $type;

  /**
   * The Rule key.
   *
   * @var string
   */
  protected $key;

  /**
   * The Prompt message type.
   *
   * @var string
   */
  protected $message_type;
  
  /**
   * The Prompt message in force mode.
   *
   * @var boolean
   */
  protected $force;

  /**
   * The Prompt message.
   *
   * @var string
   */
  protected $message;

  /**
   * The Managed entity display mode.
   *
   * @var array
   */
  protected $params;

  /**
 * @return the $force
 */
public function getForce() {
    return $this->force;
}


/**
 * @param boolean $force
 */
public function setForce($force) {
  $this->force = $force;
}


/**
   * Get Message type.
   *
   * @return the
   *   Message type.
   */
  public function getMessageType() {
    return $this->message_type;
  }

  /**
   * Set message type.
   *
   * @param string $messageType
   *   Message type.
   */
  public function setMessageType($messageType) {
    $this->message_type = $messageType;
  }

  /**
   * Get query key.
   *
   * @return the
   *   Query key.
   */
  public function getKey() {
    return $this->key;
  }

  /**
   * Set query key.
   *
   * @param string $key
   *   Query key.
   */
  public function setKey($key) {
    $this->key = $key;
  }

  /**
   * Get plugin type.
   *
   * @return the
   *   Plugin type.
   */
  public function getType() {
    return $this->type;
  }

  /**
   * Get message.
   *
   * @return the
   *   Message.
   */
  public function getMessage() {
    return $this->message;
  }

  /**
   * Set plugin type.
   *
   * @param string $type
   *   Plugin type.
   */
  public function setType($type) {
    $this->type = $type;
  }

  /**
   * Set Message.
   *
   * @param string $message
   *   Message.
   */
  public function setMessage($message) {
    $this->message = $message;
  }

  /**
   * Get params.
   *
   * @return the
   *   Params.
   */
  public function getParams() {
    return $this->params;
  }

  /**
   * Set params.
   *
   * @param array $params
   *   Params.
   */
  public function setParams(array $params) {
    $this->params = $params;
  }

}
