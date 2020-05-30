<?php

namespace Drupal\dyniva_core\Form;

use Drupal\node\Entity\Node;
use Symfony\Cmf\Component\Routing\RouteObjectInterface;
use Drupal\Core\Url;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\message\Entity\Message;

/**
 * Implements dyniva_core form.
 */
class CcmsCoreFormEntity extends FormBase implements ContainerInjectionInterface {

  protected $renderer;

  protected $entity;

  /**
   * Constructs a RevisionsController object.
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
  public function getFormId($ccms_entity = []) {

    $current_path = \Drupal::service('path.current')->getPath();
    $arr = explode('/', $current_path);
    // Landing page.
    foreach ($arr as $points => $elements) {
      if ($elements == 'content') {
        $arr[$points] = 'landing_page';
      }
    }
    $ccms_infos = dyniva_core_get_ccms_entity_info();
    foreach ($ccms_infos as $name => $values) {
      foreach ($arr as $point => $element) {
        if ($name == $element) {
          return 'dyniva_core_form_' . $name;
        }
      }
    }
    return 'dyniva_core_form_entity';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $ccms_entity = [], $node = NULL) {
    $is_new = TRUE;

    $params = UrlHelper::filterQueryParameters(\Drupal::request()->query->all());

    // Manage entity create.
    if (empty($node)) {
      $title_str = 'Create ' . $ccms_entity['label'];
      $form['#title'] = t($title_str);
      $entity = \Drupal::service('entity_type.manager')->getStorage($ccms_entity['entity_type'])->create(['type' => $ccms_entity['bundle']]);
      // $form = \Drupal::service('entity.form_builder')->getForm($entity);
      // $form = \Drupal::service('entity_type.manager')->getStorage('entity_form_display')->load('node.table.default');.
      $this->setEntity($entity);
      $request = \Drupal::request();
      if ($route = $request->attributes->get(RouteObjectInterface::ROUTE_OBJECT)) {
        $route->setDefault('_title', 'Create ' . $ccms_entity['label']);
      }
    }
    else {
      $is_new = FALSE;
      $entity = Node::load($node);
      $this->setEntity($entity);
      $title_str = 'Edit ' . $ccms_entity['label'];
      $form['#title'] = t($title_str) . $entity->getTitle();
      $form['entity'] = [
        '#type' => 'value',
        '#value' => $entity,
      ];

      $request = \Drupal::request();
      if ($route = $request->attributes->get(RouteObjectInterface::ROUTE_OBJECT)) {
        $route->setDefault('_title', 'Edit ' . $ccms_entity['label'] . ' ' . $entity->getTitle());
      }
    }
    $display = EntityFormDisplay::collectRenderDisplay($entity, 'default');
    $display->buildForm($entity, $form, $form_state);

    if ($ccms_entity['entity_type'] == 'node' && !isset($form['title_field'])) {
      $form['title'] = [
        '#type' => 'textfield',
        '#title' => t('Title'),
        '#required' => TRUE,
        '#default_value' => $entity->getTitle(),
        '#weight' => -100,
      ];
    }

    if (!empty($params)) {
      foreach ($params as $key => $value) {
        $form[$key] = [
          '#type' => 'value',
          '#value' => $value,
        ];
      }
    }

    $form['is_new'] = [
      '#type' => 'value',
      '#value' => $is_new,
    ];

    $form['entity_type'] = [
      '#type' => 'value',
      '#value' => $ccms_entity['entity_type'],
    ];

    $form['type'] = [
      '#type' => 'value',
      '#value' => $ccms_entity['bundle'],
    ];

    $form['actions'] = [
      '#type' => 'actions',
    ];

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save'),
    ];
    if (!$is_new) {
      $form['actions']['delete'] = [
        '#markup' => \Drupal::l($this->t('Delete'), Url::fromuri('internal:' . '/manage/' . $ccms_entity['bundle'] . '/' . $node . '/delete')),
      ];
    }
    $destination = !empty($form['destination']) ? $form['destination']['#value'] : '/manage/' . $ccms_entity['bundle'];
    $c_attributes = [
      'attributes' => [
        'class' => ['button'],
      ],
    ];
    $cancel_url = dyniva_core_generate_link($destination, 'internal', $c_attributes);
    $form['actions']['cancel'] = [
      '#markup' => \Drupal::l($this->t('Cancel'), $cancel_url),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {

  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Remove button and internal Form API values from submitted values.
    $values = $form_state->cleanValues()->getValues();
    if ($values['entity_type'] == 'node') {
      $created_value = $values['created'][0]['value'];
      unset($values['created']);
      // Save the entity after create.
      if ($values['is_new']) {
        $node = \Drupal::service('entity_type.manager')->getStorage($values['entity_type'])->create($values);
        $node->set('created', $created_value->format('U'));
        // $node = \Drupal::service('entity_type.manager')->getStorage($values['entity_type'])->create($values);
        $node->save();
        drupal_set_message(t('@type %title has been created.', ['@type' => ucfirst($node->getType()), '%title' => $node->getTitle()]));
        // Add entity creation message info for activities view.
        $message = Message::create(['template' => 'log_new_content', 'uid' => \Drupal::currentUser()->id()]);
        $message->set('content_reference', $node);
        $message->save();
        // Update the entity.
      }
      else {
        $node = clone $values['entity'];
        unset($values['entity']);
        unset($values['destination']);
        unset($values['is_new']);
        unset($values['entity_type']);

        foreach ($values as $key => $value) {
          $node->set($key, $value);
        }
        $node->set('created', $created_value->format('U'));
        $node->save();
        drupal_set_message(t('@type %title has been updated.', ['@type' => ucfirst($node->getType()), '%title' => $node->getTitle()]));
        // Add entity update message info for activities view.
        $message = Message::create(['template' => 'log_update_content', 'uid' => \Drupal::currentUser()->id()]);
        $message->set('content_reference', $node);
        $message->save();
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getEntity() {
    return $this->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function setEntity(EntityInterface $entity) {
    $this->entity = $entity;
    return $this;
  }

}
