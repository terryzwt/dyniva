<?php

namespace Drupal\dyniva_prompt_message\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\dyniva_prompt_message\Plugin\PromptRulePluginManager;

/**
 * Class PromptRuleForm.
 *
 * @package Drupal\dyniva_prompt_message\Form
 */
class PromptRuleForm extends EntityForm {

  /**
   * Managed entity plugin.
   *
   * @var \Drupal\dyniva_prompt_message\Plugin\PromptRulePluginManager
   */
  protected $promptRulePlugin;

  /**
   * Construct.
   *
   * @param \Drupal\dyniva_prompt_message\Plugin\PromptRulePluginManager $promptRulePlugin
   *   Plugin manager.
   */
  public function __construct(PromptRulePluginManager $promptRulePlugin) {
    $this->promptRulePlugin = $promptRulePlugin;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
        $container->get('plugin.manager.prompt_rule_plugin')
        );
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    /* @var \Drupal\dyniva_prompt_message\Entity\PromptRule $entity */
    $entity = $this->entity;
    $config = $entity->getParams();
    $type = $entity->getType();
    if ($form_state->getValue('type')) {
      $type = $form_state->getValue('type');
    }
    if ($form_state->getValue(['config', 'data'])) {
      $config = $form_state->getValue(['config', 'data']);
    }

    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $entity->label(),
      '#description' => $this->t("Label for the prompt rule."),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $entity->id(),
      '#machine_name' => [
        'exists' => '\Drupal\dyniva_prompt_message\Entity\PromptRule::load',
      ],
      '#disabled' => !$entity->isNew(),
    ];
    $form['message_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Prompt Message Type'),
      '#rows' => 4,
      '#default_value' => $entity->getMessageType(),
      '#options' => [
        'status' => $this->t('Info'),
        'warning' => $this->t('Warning'),
        'error' => $this->t('Error'),
      ],
      '#description' => $this->t("Prompt message Type."),
      '#required' => TRUE,
    ];
    $form['force'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Prompt Message in force mode'),
      '#default_value' => $entity->getForce(),
      '#required' => FALSE,
    ];
    $form['message'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Prompt Message'),
      '#rows' => 4,
      '#default_value' => $entity->getMessage(),
      '#description' => $this->t("Prompt message."),
      '#required' => TRUE,
    ];

    $form['type'] = [
      '#type' => 'select',
      '#title' => $this->t('Rule type'),
      '#required' => TRUE,
      '#options' => $this->promptRulePlugin->getSelectOptions(),
      '#default_value' => $type,
      '#ajax' => [
        'callback' => '::pluginTypeCallback',
        'wrapper' => 'config-wrapper',
      ],
    ];

    $form['config'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Rule config'),
      '#tree' => TRUE,
      '#prefix' => '<div id="config-wrapper">',
      '#suffix' => '</div>',
      'data' => [],
    ];

    $config_form = $this->promptRulePlugin->getConfigForm($type, $config);
    $form['config']['data'] = $config_form;

    return $form;
  }

  /**
   * Callback for both ajax-enabled buttons.
   *
   * Selects and returns the fieldset with the names in it.
   */
  public static function pluginTypeCallback(array &$form, FormStateInterface $form_state) {
    $form_state->setRebuild(TRUE);
    return $form['config'];
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $entity = $this->entity;
    $config = $form_state->getValue('config');
    $entity->setParams($config['data']);
    $key = $this->promptRulePlugin->getKey($entity);
    $entity->setKey($key);
    $status = parent::save($form, $form_state);

    switch ($status) {
      case SAVED_NEW:
        drupal_set_message($this->t('Created the %label Rule.', [
          '%label' => $entity->label(),
        ]));

        \Drupal::service("router.builder")->rebuild();
        break;

      default:
        drupal_set_message($this->t('Saved the %label Rule.', [
          '%label' => $entity->label(),
        ]));
    }
    $form_state->setRedirect('entity.prompt_rule.collection');
  }

}
