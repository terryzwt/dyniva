<?php

namespace Drupal\dyniva_core;

use Drupal\Core\Routing\UrlGeneratorTrait;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Provides dynamic permissions for nodes of different types.
 */
class CCMSCorePermissions implements ContainerInjectionInterface {

  use StringTranslationTrait;
  use UrlGeneratorTrait;

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new RouteSubscriber object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_manager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_manager) {
    $this->entityTypeManager = $entity_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.manager')
    );
  }

  /**
   * Returns an array of node type permissions.
   *
   * @see \Drupal\user\PermissionHandlerInterface::getPermissions()
   */
  public function ccmsCorePermissions() {
    $perms = [];
    $plugin_manager = \Drupal::service('plugin.manager.managed_entity_plugin');
    $plugins = $plugin_manager->getDefinitions();
    $managedEntities = $this->entityTypeManager->getStorage('managed_entity')->loadMultiple();
    foreach ($managedEntities as $entity) {
      $type_params = ['%type_name' => $entity->label()];
      $perms["manage ccms {$entity->id()}"] = [
        'title' => $this->t("%type_name: Access management", $type_params),
      ];
      $perms["manage ccms {$entity->id()} settings"] = [
        'title' => $this->t("%type_name: Manage settings", $type_params),
      ];
      $perms["add ccms {$entity->id()}"] = [
        'title' => $this->t("%type_name: Create new entity", $type_params),
      ];
      $perms["edit own ccms {$entity->id()}"] = [
        'title' => $this->t("%type_name: Edit own entity", $type_params),
      ];
      $perms["edit any ccms {$entity->id()}"] = [
        'title' => $this->t("%type_name: Edit any entity", $type_params),
      ];
      $perms["delete own ccms {$entity->id()}"] = [
        'title' => $this->t("%type_name: Delete own entity", $type_params),
      ];
      $perms["delete any ccms {$entity->id()}"] = [
        'title' => $this->t("%type_name: Delete any entity", $type_params),
      ];
      $perms["import ccms {$entity->id()}"] = [
        'title' => $this->t("%type_name: Import entity", $type_params),
      ];
      $perms["archive ccms {$entity->id()}"] = [
        'title' => $this->t("%type_name: Archive entity", $type_params),
      ];

      foreach ($plugins as $p){
        if ($plugin_manager->isPluginEnable($entity,$p['id'])) {
          $perms["manage ccms {$entity->id()} {$p['id']}"] = array(
            'title' => $this->t("%type_name: Manage {$p['label']}", $type_params),
          );
          
          if($p['id'] == 'revision'){
            $perms["manage ccms {$entity->id()} revision"] = array(
              'title' => $this->t("%type_name: View revisions", $type_params),
            );
            $perms["revert ccms {$entity->id()} revision"] = array(
              'title' => $this->t("%type_name: Revert revisions", $type_params),
            );
          }
        }
      }

    }

    /* Generate ccms node type settings, the role is always webmaster.
     * $perms['manage ccms feature settings'] = array(
     *   'title' => t('Manage ccms feature settings'),
     * );
     */

    /* Generate ccms system settings, the role is always admin.
     * $perms['manage ccms system settings'] = array(
     *   'title' => t('Manage ccms system settings'),
     * );
     */

    return $perms;
  }

}
