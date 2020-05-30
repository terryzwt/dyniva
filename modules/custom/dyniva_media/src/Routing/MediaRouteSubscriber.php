<?php

namespace Drupal\dyniva_media\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\Routing\Route;
use Drupal\Core\Entity\EntityInterface;

/**
 * Class CcmsMediaRouteSubscriber.
 *
 * @package Drupal\dyniva_media\Routing
 *          Listens to the dynamic route events.
 */
class MediaRouteSubscriber extends RouteSubscriberBase {
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
  protected function alterRoutes(RouteCollection $collection) {
    $managedEntitys = $this->entityTypeManager->getStorage('managed_entity')->loadByProperties(['entity_type' => 'media']);
    foreach ($managedEntitys as $entity) {
      if ($route = $this->getManagedEntityBulkAddRoute($entity)) {
        $collection->add("dyniva_media.managed_entity.{$entity->id()}.bulk_add_page", $route);
      }
    }
  }

  /**
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   * @return \Symfony\Component\Routing\Route
   */
  protected function getManagedEntityBulkAddRoute(EntityInterface $entity) {
    $id = $entity->id();
    $route = new Route("/manage/{$entity->getPath()}/bulk-add");
    $route->addDefaults([
      '_form' => '\Drupal\dyniva_media\Form\BulkUploadForm',
      '_title' => 'Bulk upload',
      'managed_entity' => $id,
      'bundle' => $entity->getBundle(),
    ])->setRequirement('_permission', "ccms bulk create {$entity->id()}");
    return $route;
  }

}
