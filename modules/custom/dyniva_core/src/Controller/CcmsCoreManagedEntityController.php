<?php

namespace Drupal\dyniva_core\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\dyniva_core\Plugin\ManagedEntityPluginManager;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class CcmsCoreManagedEntityController.
 *
 * @package Drupal\dyniva_core\Controller
 */
class CcmsCoreManagedEntityController extends ControllerBase implements ContainerInjectionInterface {

  /**
   * The date formatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * The renderer service.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $account;

  /**
   * The managed entity plugin manager.
   *
   * @var \Drupal\dyniva_core\Plugin\ManagedEntityPluginManager
   */
  protected $managedEntityPluginManager;

  /**
   * Constructs.
   *
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   *   Date formatter.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   Renderer service.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   User account.
   * @param \Drupal\dyniva_core\Plugin\ManagedEntityPluginManager $managedEntityPluginManager
   *   Managed entity plugin manager.
   */
  public function __construct(DateFormatterInterface $date_formatter, RendererInterface $renderer, AccountInterface $account, ManagedEntityPluginManager $managedEntityPluginManager) {
    $this->dateFormatter = $date_formatter;
    $this->renderer = $renderer;
    $this->account = $account;
    $this->managedEntityPluginManager = $managedEntityPluginManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
        $container->get('date.formatter'),
        $container->get('renderer'),
        $container->get('current_user'),
        $container->get('plugin.manager.managed_entity_plugin')
        );
  }

  /**
   * Provides the entity add form.
   *
   * @param \Drupal\Core\Entity\EntityInterface $managed_entity
   *   Managed entity.
   *
   * @return array
   *   Render array.
   */
  public function entityAdd(EntityInterface $managed_entity) {
    $entity_id = $managed_entity->get('entity_type');
    $entity_type = $this->entityTypeManager()->getDefinition($entity_id);
    $bundle = $managed_entity->get('bundle');
    if ($bundle != 'und') {
      $entity_interface = $this->entityTypeManager()->getStorage($entity_id)->create([
        $entity_type->getKey('bundle') => $bundle,
        $entity_type->getKey('uid') => $this->account->id(),
      ]);
      if($entity_type->getFormClass("add")){
        $entity_form = $this->entityFormBuilder()->getForm($entity_interface,'add');
      }else if($entity_type->getFormClass($managed_entity->get('form_mode'))){
        $entity_form = $this->entityFormBuilder()->getForm($entity_interface, $managed_entity->get('form_mode'));
      }else {
        $entity_form = $this->entityFormBuilder()->getForm($entity_interface, 'default');
      }
      
    }
    else {
      $entity_form = \Drupal::formBuilder()->getForm('\Drupal\dyniva_core\Form\AddEntityMultiStepForm', $managed_entity);
    }

    return $entity_form;
  }

  /**
   * The _title_callback for the entity.add routes.
   *
   * @param \Drupal\Core\Entity\EntityInterface $managed_entity
   *   Managed entity.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   *   Title
   */
  public function addPageTitle(EntityInterface $managed_entity) {
    return $this->t('Create @name', [
      '@name' => $managed_entity->label(),
    ]);
  }

  /**
   * Provides the entity edit form.
   *
   * @param \Drupal\Core\Entity\EntityInterface $managed_entity
   *   Managed entity.
   * @param \Drupal\Core\Entity\EntityInterface $managed_entity_id
   *   Managed entity id.
   *
   * @return array
   *   Render array.
   */
  public function entityView(EntityInterface $managed_entity, EntityInterface $managed_entity_id) {

    $view_builder = \Drupal::entityTypeManager()
      ->getViewBuilder($managed_entity_id->getEntityTypeId());
    $view = $view_builder->view($managed_entity_id, $managed_entity->get('display_mode'));

    return $view;
  }

  /**
   * Title callback.
   *
   * @param \Drupal\Core\Entity\EntityInterface $managed_entity
   *   Managed entity.
   * @param \Drupal\Core\Entity\EntityInterface $managed_entity_id
   *   Managed entity id.
   *
   * @return string|null
   *   Title.
   */
  public function viewPageTitle(EntityInterface $managed_entity, EntityInterface $managed_entity_id) {
    return $managed_entity_id->label();
  }

  /**
   * Provides the entity edit form.
   *
   * @param \Drupal\Core\Entity\EntityInterface $managed_entity
   *   Managed entity.
   * @param \Drupal\Core\Entity\EntityInterface $managed_entity_id
   *   Managed entity id.
   *
   * @return bool|array
   *   Render array.
   */
  public function entityEdit(EntityInterface $managed_entity, EntityInterface $managed_entity_id) {

    if($managed_entity_id->getEntityType()->getFormClass($managed_entity->get('form_mode'))){
      $entity_form = $this->entityFormBuilder()->getForm($managed_entity_id, $managed_entity->get('form_mode'));
    }else if($managed_entity_id->getEntityType()->getFormClass("edit")){
      $entity_form = $this->entityFormBuilder()->getForm($managed_entity_id,'edit');
    }else {
      $entity_form = $this->entityFormBuilder()->getForm($managed_entity_id, 'default');
    }

    if ($managed_entity_id->getEntityTypeId() == 'node') {
      $entity_form['actions']['delete']['#access'] = FALSE;
      $entity_form['actions']['unlock']['#access'] = FALSE;
    }
    return $entity_form;
  }

  /**
   * Title callback.
   *
   * @param \Drupal\Core\Entity\EntityInterface $managed_entity
   *   Managed entity.
   * @param \Drupal\Core\Entity\EntityInterface $managed_entity_id
   *   Managed entity id.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   *   Title.
   */
  public function editPageTitle(EntityInterface $managed_entity, EntityInterface $managed_entity_id) {
    return $this->t('Edit @name @label', [
      '@name' => $managed_entity->label(),
      '@label' => $managed_entity_id->label(),
    ]);
  }

  /**
   * Provides the entity edit form.
   *
   * @param \Drupal\Core\Entity\EntityInterface $managed_entity
   *   Managed entity.
   * @param \Drupal\Core\Entity\EntityInterface $managed_entity_id
   *   Managed entity id.
   *
   * @return array
   *   Render array.
   */
  public function entityDelete(EntityInterface $managed_entity, EntityInterface $managed_entity_id) {

    $entity_form = $this->entityFormBuilder()->getForm($managed_entity_id, 'delete');

    return $entity_form;
  }

  /**
   * Title callback.
   *
   * @param \Drupal\Core\Entity\EntityInterface $managed_entity
   *   Managed entity.
   * @param \Drupal\Core\Entity\EntityInterface $managed_entity_id
   *   Managed entity id.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   *   Title.
   */
  public function deletePageTitle(EntityInterface $managed_entity, EntityInterface $managed_entity_id) {
    return $this->t('Delete @name @label', [
      '@name' => $managed_entity->label(),
      '@label' => $managed_entity_id->label(),
    ]);
  }

  /**
   * Title callback.
   *
   * @param \Drupal\Core\Entity\EntityInterface $managed_entity
   *   Managed entity.
   * @param \Drupal\Core\Entity\EntityInterface $managed_entity_id
   *   Managed entity id.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   *   Title.
   */
  public function publishPageTitle(EntityInterface $managed_entity, EntityInterface $managed_entity_id) {
    $action = 'Publish';
    if ($managed_entity_id->get('status')) {
      $action = 'Unpublish';
    }
    return $this->t($action . ' @name @label', [
      '@name' => $managed_entity->label(),
      '@label' => $managed_entity_id->label(),
    ]);
  }

  /**
   * Title callback.
   *
   * @param \Drupal\Core\Entity\EntityInterface $managed_entity
   *   Managed entity.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   *   Title.
   */
  public function importPageTitle(EntityInterface $managed_entity) {
    return $this->t('Import @name', [
      '@name' => $managed_entity->label(),
    ]);
  }

  /**
   * Access callback.
   *
   * @param string $form_display
   *   Form display.
   * @param EntityTypeInterface $entity_type
   *   Entity Type.
   *
   * @return unknown
   *   Access result.
   */
  public function checkAccess($form_display, EntityTypeInterface $entity_type) {
    return AccessResult::allowedIfHasPermission($this->currentUser(), "use {$entity_type->id()}.{$form_display} form mode")->cachePerPermissions();
  }

  /**
   * Provides the entity add form.
   *
   * @param \Drupal\Core\Entity\EntityInterface $managed_entity
   *   Managed entity.
   * @param \Drupal\Core\Entity\EntityInterface $managed_entity_id
   *   Managed entity id.
   * @param string $plugin_id
   *   Plugin id.
   *
   * @return unknown
   *   Render array.
   */
  public function pluginPage(EntityInterface $managed_entity, EntityInterface $managed_entity_id, $plugin_id) {
    $instance = $this->managedEntityPluginManager->createInstance($plugin_id);
    $output = $instance->buildPage($managed_entity, $managed_entity_id);
    return $output;
  }

  /**
   * Title callback.
   *
   * @param \Drupal\Core\Entity\EntityInterface $managed_entity
   *   Managed entity.
   * @param \Drupal\Core\Entity\EntityInterface $managed_entity_id
   *   Managed entity id.
   * @param string $plugin_id
   *   Plugin id.
   *
   * @return unknown
   *   Title.
   */
  public function pluginPageTitle(EntityInterface $managed_entity, EntityInterface $managed_entity_id, $plugin_id) {
    $instance = $this->managedEntityPluginManager->createInstance($plugin_id);
    $title = $instance->getPageTitle($managed_entity, $managed_entity_id);
    return $title;
  }

  /**
   * Create message.
   *
   * @param array $form
   *   Form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state object.
   * @param \Drupal\Core\Entity\EntityInterface $managed_entity
   *   Managed entity.
   */
  public static function createMessage(array &$form, FormStateInterface $form_state, EntityInterface $managed_entity) {

  }

}
