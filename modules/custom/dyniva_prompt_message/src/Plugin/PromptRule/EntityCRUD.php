<?php

namespace Drupal\dyniva_prompt_message\Plugin\PromptRule;

use Drupal\dyniva_prompt_message\Plugin\PromptRulePluginBase;
use Drupal\dyniva_prompt_message\Form\PromptRuleForm;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\dyniva_prompt_message\Entity\PromptRule;

/**
 * Entity CRUD Rule.
 *
 * @PromptRule(
 *  id = "entity_crud",
 *  label = @Translation("Entity CRUD")
 * )
 */
class EntityCRUD extends PromptRulePluginBase {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function getMessage(array $context = []) {
    $messages = [];
    if (!empty($context['entity']) && !empty($context['op'])) {
      $entity = $context['entity'];
      $op = $context['op'];
      $key = $this->getKeyFromContext($context);
      $rules = $this->getRules($key);
      foreach ($rules as $rule) {
        $params = $rule->getParams();
        $events = array_filter($params['events']);
        if (in_array($op, $events)) {
          $messages[] = $rule;
        }
      }
    }
    return $messages;
  }

  /**
   * Get query key from context.
   */
  protected function getKeyFromContext($context) {
    $entity = $context['entity'];
    return "{$entity->getEntityTypeId()}-{$entity->bundle()}";
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigForm($config = []) {
    $form = [];

    $entity_types = \Drupal::entityTypeManager()->getDefinitions();
    $type_options = [];
    foreach ($entity_types as $key => $type) {
      if (is_string($type->getLabel())) {
        $type_options[$key] = $type->getLabel();
      }
      else {
        $type_options[$key] = ucfirst($type->getLabel()->render());
      }
    }
    asort($type_options);
    $entity_type = !empty($config['entity_type']) ? $config['entity_type'] : current(array_keys($type_options));
    $form['entity_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Entity type'),
      '#required' => TRUE,
      '#default_value' => $entity_type,
      '#options' => $type_options,
      '#ajax' => [
        'callback' => [PromptRuleForm::class, 'pluginTypeCallback'],
        'wrapper' => 'config-wrapper',
      ],
    ];
    $bundle_options = [];
    if (!empty($entity_type)) {
      $entity_bundles = \Drupal::entityManager()->getBundleInfo($entity_type);
      foreach ($entity_bundles as $key => $item) {
        $bundle_options[$key] = $item['label'];
      }
    }
    $form['bundle'] = [
      '#type' => 'select',
      '#title' => $this->t('Bundle'),
      '#default_value' => $config['bundle']?:'',
      '#options' => $bundle_options,
      '#prefix' => '<div id="bundle-wrapper">',
      '#suffix' => '</div>',
    ];
    $events_options = [
      'insert' => $this->t('Insert'),
      'update' => $this->t('Update'),
      'delete' => $this->t('Delete'),
    ];
    $form['events'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Events'),
      '#default_value' => $config['events']?:[],
      '#options' => $events_options,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function getKey(PromptRule $rule) {
    $params = $rule->getParams();
    return $params['entity_type'] . '-' . $params['bundle'];
  }

}
