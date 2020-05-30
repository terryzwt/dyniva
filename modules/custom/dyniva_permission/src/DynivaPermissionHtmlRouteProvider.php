<?php

namespace Drupal\dyniva_permission;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\Routing\DefaultHtmlRouteProvider;

/**
 * Provides routes for Deployment entity entities.
 *
 * @see Drupal\Core\Entity\Routing\AdminHtmlRouteProvider
 * @see Drupal\Core\Entity\Routing\DefaultHtmlRouteProvider
 */
class DynivaPermissionHtmlRouteProvider extends DefaultHtmlRouteProvider {
  /**
   * {@inheritdoc}
   */
  public function getRoutes(EntityTypeInterface $entity_type) {
    $collection = parent::getRoutes($entity_type);

    $entity_type_id = $entity_type->id();

    if($route = $collection->get("entity.{$entity_type_id}.delete_form")){
      $route->setRequirements([
        '_permission' => 'manage ccms user'
      ]);
    }

    return $collection;
  }
}
