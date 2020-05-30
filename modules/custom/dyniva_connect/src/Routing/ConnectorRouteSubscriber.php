<?php

namespace Drupal\dyniva_connect\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\Routing\Route;
use Drupal\dyniva_connect\Entity\Connector;

/**
 * Class WlCoreRouteSubscriber.
 *
 * @package Drupal\dyniva_connect\Routing
 *          Listens to the dynamic route events.
 */
class ConnectorRouteSubscriber extends RouteSubscriberBase {
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
   *          The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_manager) {
    $this->entityTypeManager = $entity_manager;
  }

  /**
   *
   * {@inheritdoc}
   *
   */
  protected function alterRoutes(RouteCollection $collection) {
    $connectors = $this->entityTypeManager->getStorage('connector')->loadMultiple();
    foreach($connectors as $entity) {
      if ($route = $this->getConnectorConnectRoute($entity)) {
        $collection->add("dyniva_connect.connector.{$entity->id()}.connect", $route);
      }
      if ($route = $this->getConnectorMessageRoute($entity)) {
        $collection->add("dyniva_connect.connector.{$entity->id()}.message", $route);
      }
    }
  }

  /**
   *
   * @param Connector $entity          
   * @return \Symfony\Component\Routing\Route
   */
  protected function getConnectorConnectRoute(Connector $entity) {
    $id = $entity->id();
    $route = new Route("/connect/{$id}");
    $route->addDefaults([
      '_controller' => '\Drupal\dyniva_connect\Controller\ConnectController::connect',
      '_title' => 'User connect',
      'connector' => $id
    ])->setRequirement('_access', 'TRUE');
    
    return $route;
  }
  /**
   *
   * @param Connector $entity          
   * @return \Symfony\Component\Routing\Route
   */
  protected function getConnectorMessageRoute(Connector $entity) {
    $id = $entity->id();
    $route = new Route("/connector/{$id}/message");
    $route->addDefaults([
      '_controller' => '\Drupal\dyniva_connect\Controller\ConnectController::message',
      '_title' => 'User connect',
      'connector' => $id
    ])->setRequirement('_access', 'TRUE');
    
    return $route;
  }
}
