<?php

namespace Drupal\dyniva_core\Form;

use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\dyniva_core\Plugin\ManagedEntityPluginManager;
use Drupal\dyniva_core\Entity\ManagedEntity;
use Drupal\inline_entity_form\ElementSubmit;

/**
 * Class AddEntityMultiStepForm.
 *
 * @package Drupal\dyniva_core\Form
 */
class AddEntityMultiStepForm extends FormBase {

  /**
   * Managed entity plugin.
   *
   * @var \Drupal\dyniva_core\Plugin\ManagedEntityPluginManager
   */
  protected $managedEntityPlugin;

  /**
   * Construct.
   *
   * @param \Drupal\dyniva_core\Plugin\ManagedEntityPluginManager $managedEntityPlugin
   *   Managed entity plugin.
   */
  public function __construct(ManagedEntityPluginManager $managedEntityPlugin) {
    $this->managedEntityPlugin = $managedEntityPlugin;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('plugin.manager.managed_entity_plugin'));
  }

  /**
   * {@inheritdoc}
   *
   * @see \Drupal\Core\Form\FormInterface::getFormId()
   */
  public function getFormId() {
    return 'dyniva_core_add_entity_multi_step_form';
  }

  /**
   * {@inheritdoc}
   *
   * @see \Drupal\Core\Form\FormInterface::buildForm()
   */
  public function buildForm(array $form, FormStateInterface $form_state, ManagedEntity $managed_entity = NULL) {

    if ($managed_entity) {
      $form_state->set('managed_entity', $managed_entity);
    }
    else {
      $managed_entity = $form_state->get('managed_entity');
    }

    $language = \Drupal::languageManager()->getCurrentLanguage()->getId();
    $form_state->set('langcode', $language);

    $form['#prefix'] = '<div id="entity-wrapper">';
    $form['#suffix'] = '</div>';

    $form['actions'] = [
      '#type' => 'actions',
      '#weight' => 99,
    ];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => t('Submit'),
    ];

    $bundle_options = ['und' => t('Not Specified')];
    if (!empty($managed_entity->get('entity_type'))) {
      $entity_bundles = \Drupal::entityManager()->getBundleInfo($managed_entity->get('entity_type'));
      foreach ($entity_bundles as $key => $item) {
        $bundle_options[$key] = $item['label'];
      }
    }
    $bundle = $form_state->getValue('bundle', 'und');
    $form['bundle'] = [
      '#type' => 'select',
      '#title' => $this->t('Select @label Type To Add', ['@label' => $managed_entity->label()]),
      '#default_value' => $bundle,
      '#options' => $bundle_options,

    ];

    if ($entity = $form_state->get('entity')) {
      $form['bundle']['#disabled'] = TRUE;
      $form['entity'] = [
        '#type' => 'inline_entity_form',
        '#entity_type' => $entity->getEntityTypeId(),
        '#bundle' => $entity->bundle(),
        '#default_value' => $entity,
      ];

      ElementSubmit::addCallback($form['actions']['submit'], $form_state->getCompleteForm());
    }
    else {
      $form['actions']['submit']['#ajax'] = [
        'wrapper' => 'entity-wrapper',
        'callback' => [static::class, 'ajax'],
      ];
      $form['actions']['submit']['#value'] = t('Create');
    }

    return $form;
  }

  /**
   * Ajax callback.
   *
   * @param array $form
   *   Form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state.
   *
   * @return array
   *   Form array.
   */
  public static function ajax(array &$form, FormStateInterface $form_state) {
    return $form;
  }

  /**
   * {@inheritdoc}
   *
   * @see \Drupal\Core\Form\FormInterface::submitForm()
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $entity = isset($form['entity']['#entity']) ? $form['entity']['#entity'] : FALSE;
    if ($entity) {
      // $entity->save();
    }
    else {
      $bundle = $form_state->getValue('bundle');
      if ($bundle && $bundle != 'und') {
        $managed_entity = $form_state->get('managed_entity');
        $entity_id = $managed_entity->get('entity_type');
        $entity_type = \Drupal::entityTypeManager()->getDefinition($entity_id);
        $entity_interface = \Drupal::entityTypeManager()->getStorage($entity_id)->create([
          $entity_type->getKey('bundle') => $bundle,
          $entity_type->getKey('uid')?:'uid' => \Drupal::currentUser()->id(),
        ]);
        $form_state->set('entity', $entity_interface);
        $form_state->setRebuild();
      }
    }
  }

}
