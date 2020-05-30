<?php

namespace Drupal\dyniva_core\Form;

use Symfony\Cmf\Component\Routing\RouteObjectInterface;
use Drupal\node\Entity\Node;
use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Url;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;

/**
 * Entity delete form.
 */
class CcmsCoreFormEntityDelete extends FormBase implements ContainerInjectionInterface {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    $current_path = \Drupal::service('path.current')->getPath();
    $arr = explode('/', $current_path);
    $ccms_infos = dyniva_core_get_ccms_entity_info();
    foreach ($ccms_infos as $name => $values) {
      foreach ($arr as $point => $element) {
        if ($name == $element) {
          return 'dyniva_core_form_' . $name . '_delete';
        }
      }
    }
    return 'dyniva_core_form_entity_delete';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $ccms_entity = [], $node = NULL) {
    $entity = Node::load($node);
    $form['#title'] = $this->t('Are you sure you want to delete %title?', ['%title' => $entity->getTitle()]);
    $request = \Drupal::request();
    if ($route = $request->attributes->get(RouteObjectInterface::ROUTE_OBJECT)) {
      $route->setDefault('_title', 'Are you sure you want to delete ' . $entity->getTitle() . '?');
    }

    $params = UrlHelper::filterQueryParameters(\Drupal::request()->query->all());
    if (!empty($params)) {
      foreach ($params as $key => $value) {
        $form[$key] = [
          '#type' => 'value',
          '#value' => $value,
        ];
      }
    }

    $form['#markup'] = $this->t('<div>This action cannot be undone.</div>');
    $form['actions'] = [
      '#type' => 'actions',
    ];

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Delete'),
    ];

    $destination = !empty($form['destination']) ? $form['destination']['#value'] : '/manage/' . $ccms_entity['bundle'];
    $form['actions']['cancel'] = [
      '#markup' => \Drupal::l($this->t('Cancel'), Url::fromUri('internal:' . $destination)),
    ];

    $form['entity_id'] = [
      '#type' => 'value',
      '#value' => $node,
    ];
    $form['entity_type'] = [
      '#type' => 'value',
      '#value' => $ccms_entity['entity_type'],
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
      $node = Node::load($values['entity_id']);
      $title = $node->getTitle();
      $type = $node->getType();
      $node->delete();
      $form_state->setRedirect('view.' . $type . '.page_manage_' . $type);
      drupal_set_message(t('@type %title has been deleted.', ['@type' => ucfirst($type), '%title' => $title]));
    }
  }

}
