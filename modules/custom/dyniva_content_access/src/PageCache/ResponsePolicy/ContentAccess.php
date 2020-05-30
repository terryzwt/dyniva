<?php

namespace Drupal\dyniva_content_access\PageCache\ResponsePolicy;

use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Routing\CurrentRouteMatch;
use Drupal\Core\PageCache\ResponsePolicyInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * A policy allowing delivery of cached pages when there is no session open.
 *
 * Do not serve cached pages to authenticated users, or to anonymous users when
 * $_SESSION is non-empty. $_SESSION may contain status messages from a form
 * submission, the contents of a shopping cart, or other userspecific content
 * that should not be cached and displayed to other users.
 */
class ContentAccess implements ResponsePolicyInterface {

  /**
   * @var CurrentRouteMatch
   */
  protected $routeMatch;
  
  /**
   * Constructs a new page cache session policy.
   *
   * @param \Drupal\Core\Session\SessionConfigurationInterface $session_configuration
   *   The session configuration.
   */
  public function __construct(CurrentRouteMatch $routeMatch) {
    $this->routeMatch = $routeMatch;
  }
  /**
   * {@inheritdoc}
   */
  public function check(Response $response, Request $request) {
    $route = $this->routeMatch->getRouteName();
    $entity = false;
    if($route == 'entity.node.canonical'){
      $entity = $this->routeMatch->getParameter('node');
    }
    if($entity){
      if($entity instanceof \Drupal\Core\Entity\FieldableEntityInterface && $entity->hasField('access_control')){
        $flag =  false;
        foreach ($entity->access_control as $item){
          if($item->value != 'public'){
            $flag = true;
            break;
          }
        }
        if($flag){
          return static::DENY;
        }
      }
    }
  }
}
