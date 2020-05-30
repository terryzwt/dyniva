<?php

namespace Drupal\dyniva_core\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\dyniva_core\ManagedEntityInterface;
use Drupal\Component\Utility\Html;

/**
 * Defines the Managed entity entity.
 *
 * @ConfigEntityType(
 * id = "managed_entity",
 * label = @Translation("Managed entities"),
 * handlers = {
 * "list_builder" = "Drupal\dyniva_core\ManagedEntityListBuilder",
 * "form" = {
 * "add" = "Drupal\dyniva_core\Form\ManagedEntityForm",
 * "edit" = "Drupal\dyniva_core\Form\ManagedEntityForm",
 * "delete" = "Drupal\dyniva_core\Form\ManagedEntityDeleteForm"
 * },
 * "route_provider" = {
 * "html" = "Drupal\dyniva_core\ManagedEntityHtmlRouteProvider",
 * },
 * },
 * config_prefix = "managed_entity",
 * admin_permission = "administer site configuration",
 * entity_keys = {
 * "id" = "id",
 * "label" = "label",
 * "uuid" = "uuid"
 * },
 * links = {
 * "canonical" = "/admin/structure/managed_entity/{managed_entity}",
 * "add-form" = "/admin/structure/managed_entity/add",
 * "edit-form" = "/admin/structure/managed_entity/{managed_entity}/edit",
 * "delete-form" = "/admin/structure/managed_entity/{managed_entity}/delete",
 * "collection" = "/admin/structure/managed_entity"
 * }
 * )
 */
class ManagedEntity extends ConfigEntityBase implements ManagedEntityInterface {
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
   * The Managed entity's entity type.
   *
   * @var string
   */
  protected $entity_type;

  /**
   * The Managed entity's bundle.
   *
   * @var string
   */
  protected $bundle;

  /**
   * The Managed entity display mode.
   *
   * @var string
   */
  protected $display_mode;

  /**
   * The Managed entity form display mode.
   *
   * @var string
   */
  protected $form_mode;

  /**
   * The Managed entity is has_draft.
   *
   * @var bool
   */
  protected $has_draft;

  /**
   * The Managed entity is standalone.
   *
   * @var bool
   */
  protected $standalone;

  /**
   * The Managed entity plugin status.
   *
   * @var bool
   */
  protected $plugins;

  /**
   * 获取 url path.
   *
   * @return string|int|null
   *   Url path.
   */
  public function getPath() {
    $path = $this->id();
    $path = Html::getId($path);
    return $path;
  }

  /**
   * Get entity type.
   *
   * @return the
   *   The entity type.
   */
  public function getManagedEntityType() {
    return $this->entity_type;
  }

  /**
   * Get bundle.
   *
   * @return the
   *   The bundle.
   */
  public function getBundle() {
    return $this->bundle;
  }

  /**
   * Get display mode.
   *
   * @return the
   *   The display mode.
   */
  public function getDisplayMode() {
    return $this->display_mode;
  }

  /**
   * Get form mode.
   *
   * @return the
   *   The form mode.
   */
  public function getFormMode() {
    return $this->form_mode;
  }

  /**
   * Get is has draft.
   *
   * @return the
   *   The draft flag.
   */
  public function getHasDraft() {
    return $this->has_draft;
  }

  /**
   * Get standalone flag.
   *
   * @return the
   *   The standalone flag.
   */
  public function getStandalone() {
    return $this->standalone;
  }

  /**
   * Get plugins array.
   *
   * @return the
   *   The plugins.
   */
  public function getPlugins() {
    return $this->plugins;
  }

  /**
   * Set entity type.
   *
   * @param string $entity_type
   *   The entity type.
   */
  public function setManagedEntityType($entity_type) {
    $this->entity_type = $entity_type;
  }

  /**
   * Set bundle.
   *
   * @param string $bundle
   *   The bundle.
   */
  public function setBundle($bundle) {
    $this->bundle = $bundle;
  }

  /**
   * Set display mode.
   *
   * @param string $display_mode
   *   The display mode.
   */
  public function setDisplayMode($display_mode) {
    $this->display_mode = $display_mode;
  }

  /**
   * Set form mode.
   *
   * @param string $form_mode
   *   The form mode.
   */
  public function setFormMode($form_mode) {
    $this->form_mode = $form_mode;
  }

  /**
   * Set has draft flag.
   *
   * @param bool $has_draft
   *   Draft flag.
   */
  public function setHasDraft($has_draft) {
    $this->has_draft = $has_draft;
  }

  /**
   * Set standalone flag.
   *
   * @param bool $standalone
   *   The standalone.
   */
  public function setStandalone($standalone) {
    $this->standalone = $standalone;
  }

  /**
   * Set plugins.
   *
   * @param bool $plugins
   *   Plugins.
   */
  public function setPlugins($plugins) {
    $this->plugins = $plugins;
  }

}
