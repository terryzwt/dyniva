<?php

namespace Drupal\dyniva_core\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\dyniva_core\Plugin\ManagedEntityPluginManager;

/**
 * Class ManagedEntityForm.
 *
 * @package Drupal\dyniva_core\Form
 */
class ManagedEntityForm extends EntityForm {

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
    return new static(
        $container->get('plugin.manager.managed_entity_plugin')
        );
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $managed_entity = $this->entity;
    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $managed_entity->label(),
      '#description' => $this->t("Label for the Managed entity."),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $managed_entity->id(),
      '#machine_name' => [
        'exists' => '\Drupal\dyniva_core\Entity\ManagedEntity::load',
      ],
      '#disabled' => !$managed_entity->isNew(),
    ];
    $entity_types = \Drupal::entityTypeManager()->getDefinitions();
    $type_options = [];
    foreach ($entity_types as $key => $type) {
      if(is_string($type->getLabel())) {
        $type_options[$key] = $type->getLabel();
      } else {
        $type_options[$key] = ucfirst($type->getLabel()->render());
      }
    }
    asort($type_options);
    $form['entity_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Entity type'),
      '#required' => TRUE,
      '#default_value' => $managed_entity->get('entity_type'),
      '#options' => $type_options,
      '#disabled' => !$managed_entity->isNew(),
      '#ajax' => [
        'callback' => '::entityTypeCallback',
        'wrapper' => 'bundle-wrapper',
      ],
    ];
    $bundle_options = ['und' => t('Not Specified')];
    if (!empty($managed_entity->get('entity_type'))) {
      $entity_bundles = \Drupal::entityManager()->getBundleInfo($managed_entity->get('entity_type'));
      foreach ($entity_bundles as $key => $item) {
        $bundle_options[$key] = $item['label'];
      }
    }
    $form['bundle'] = [
      '#type' => 'select',
      '#title' => $this->t('Bundle'),
      '#default_value' => $managed_entity->get('bundle'),
      '#options' => $bundle_options,
      '#disabled' => !$managed_entity->isNew(),
      '#prefix' => '<div id="bundle-wrapper">',
      '#suffix' => '</div>',
    ];
    // @TODO remove
    $form['display_mode'] = [
      '#type' => 'textfield',
      '#title' => $this->t('View display mode'),
      '#maxlength' => 255,
      '#default_value' => empty($managed_entity->get('display_mode')) ? 'default' : $managed_entity->get('display_mode'),
      '#description' => $this->t("Display layout on entity view."),
      '#required' => TRUE,
    ];
    // @TODO remove
    $form['form_mode'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Form display mode'),
      '#maxlength' => 255,
      '#default_value' => empty($managed_entity->get('form_mode')) ? 'default' : $managed_entity->get('form_mode'),
      '#description' => $this->t("Form layout on entity add and edit."),
      '#required' => TRUE,
    ];

    // @TODO remove
    $form['module_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Module name'),
      '#maxlength' => 255,
      '#default_value' => empty($managed_entity->get('module_name')) ? 'dyniva_core' : $managed_entity->get('module_name'),
      '#description' => $this->t("The module machine name which defined this config object."),
      '#required' => TRUE,
    ];

    /*
    $form['has_draft'] = array(
    '#type' => 'radios',
    '#title' => 'Has draft',
    '#required' => TRUE,
    '#default_value' => $managed_entity->get('has_draft'),
    '#options' => array(
    0 => 'NO',
    1 => 'YES'
    ),
    ); */
    $form['standalone'] = [
      '#type' => 'radios',
      '#title' => $this->t('Show Action'),
      '#required' => TRUE,
      '#default_value' => $managed_entity->get('standalone'),
      '#description' => $this->t('Show action button in the views (manage_{managed_entity}:page_list).'),
      '#options' => [
        0 => 'NO',
        1 => 'YES',
      ],
    ];

    /* You will need additional form elements for your custom properties. */

    $plugins = $this->managedEntityPlugin->getDefinitions();
    if (!empty($plugins)) {
      $options = [];
      foreach ($plugins as $p) {
        $options[$p['id']] = $p['label'];
      }
      if (!\Drupal::moduleHandler()->moduleExists('content_translation')) {
        unset($options['translation']);
      }
      $form['plugins'] = [
        '#type' => 'checkboxes',
        '#title' => $this->t('Plugins'),
        '#options' => $options,
        '#default_value' => $managed_entity->get('plugins') ? $managed_entity->get('plugins') : [],
      ];
    }
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $managed_entity = $this->entity;
    $status = $managed_entity->save();

    switch ($status) {
      case SAVED_NEW:
        drupal_set_message($this->t('Created the %label Managed entity.', [
          '%label' => $managed_entity->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved the %label Managed entity.', [
          '%label' => $managed_entity->label(),
        ]));
    }
    \Drupal::service("router.builder")->rebuild();
    $form_state->setRedirectUrl($managed_entity->urlInfo('collection'));
  }

  /**
   * Callback for both ajax-enabled buttons.
   *
   * Selects and returns the fieldset with the names in it.
   */
  public function entityTypeCallback(array &$form, FormStateInterface $form_state) {
    $entity_type = $form_state->getValue('entity_type');

    $bundle_options = ['und' => t('Not Specified')];
    if (!empty($entity_type)) {
      $entity_bundles = \Drupal::entityManager()->getBundleInfo($entity_type);
      foreach ($entity_bundles as $key => $item) {
        $bundle_options[$key] = $item['label'];
      }
    }
    $form['bundle']['#options'] = $bundle_options;
    $form_state->setValue('bundle', 'und');
    return $form['bundle'];
  }

}
