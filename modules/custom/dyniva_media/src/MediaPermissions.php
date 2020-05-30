<?php

namespace Drupal\dyniva_media;

use Drupal\Core\Routing\UrlGeneratorTrait;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Provides dynamic permissions for nodes of different types.
 */
class MediaPermissions implements ContainerInjectionInterface {

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
   * @return array
   *   The dyniva_core permissions.
   *
   * @see \Drupal\user\PermissionHandlerInterface::getPermissions()
   */
  public function getPermissions() {
    $perms = [];
    $managedEntitys = $this->entityTypeManager->getStorage('managed_entity')->loadByProperties(['entity_type' => 'media']);
    foreach ($managedEntitys as $entity) {
      $type_params = ['%type_name' => $entity->label()];
      $perms["ccms bulk create {$entity->id()}"] = [
        'title' => $this->t("%type_name: Bulk create", $type_params),
      ];
    }

    return $perms;
  }

}
