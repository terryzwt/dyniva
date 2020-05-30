<?php

namespace Drupal\dyniva_core\Path;

use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Routing\RequestContext;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Url;
use Drupal\menu_trail_by_path\Path\PathHelperInterface;
use Symfony\Component\Routing\Exception\RouteNotFoundException;

/**
 * Custom Path helper.
 */
class CurrentPathHelper implements PathHelperInterface {
  /**
   * Route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * Request context.
   *
   * @var \Drupal\Core\Routing\RequestContext
   */
  private $context;

  /**
   * Construct.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   Route match.
   * @param \Drupal\Core\Routing\RequestContext $context
   *   Request context.
   */
  public function __construct(RouteMatchInterface $route_match, RequestContext $context) {
    $this->routeMatch = $route_match;
    $this->context    = $context;
  }

  /**
   * {@inheritdoc}
   */
  public function getUrls() {
    $trail_urls = $this->getCurrentPathUrls();
    if ($destination = \Drupal::request()->get('destination')) {
      $trail_urls[] = $this->createUrlFromRelativeUri($destination);
    }
    if ($current_request_url = $this->getCurrentRequestUrl()) {
      $trail_urls[] = $current_request_url;
    }

    return $trail_urls;
  }

  /**
   * Returns the current request Url.
   *
   * NOTE: There is a difference between $this->routeMatch->getRouteName
   * and $this->context->getPathInfo()
   * for now it seems more logical to prefer the latter, because that's the
   * "real" url that visitors enter in their browser..
   *
   * @return \Drupal\Core\Url|null
   *   Url.
   */
  protected function getCurrentRequestUrl() {
    $current_pathinfo_url = $this->createUrlFromRelativeUri($this->context->getPathInfo());
    if ($current_pathinfo_url->isRouted()) {
      if ($scope = \Drupal::request()->get('scope')) {
        $current_pathinfo_url->setOption('query', ['scope' => $scope]);
      }
      return $current_pathinfo_url;
    }
    elseif ($route_name = $this->routeMatch->getRouteName()) {
      $route_parameters = $this->routeMatch->getRawParameters()->all();
      $url = new Url($route_name, $route_parameters);
      if ($scope = \Drupal::request()->get('scope')) {
        $url->setOption('query', ['scope' => $scope]);
      }
      return $url;
    }

    return NULL;
  }

  /**
   * Get current path urls.
   *
   * @return array
   *   Urls array.
   */
  protected function getCurrentPathUrls() {
    $urls = [];

    $path = trim($this->context->getPathInfo(), '/');
    $path_elements = explode('/', $path);

    /*
     * @var \Drupal\Core\Menu\LocalTaskManager $localTaskManager
     */
    $localTaskManager = \Drupal::service('plugin.manager.menu.local_task');

    while (count($path_elements) > 1) {
      array_pop($path_elements);
      $url = $this->createUrlFromRelativeUri('/' . implode('/', $path_elements));
      if ($url->isRouted()) {
        $urls[] = $url;
        if (count($path_elements) == 2) {
          $tasks = $localTaskManager->getLocalTasks($url->getRouteName());
          if (!empty($tasks)) {
            foreach ($tasks['tabs'] as $key => $item) {
              if ($item['#link']['url']->getRouteName() != $url->getRouteName()) {
                $urls[] = $item['#link']['url'];
              }
            }
          }
        }
      }
    }
    if ($route_name = $this->routeMatch->getRouteName()) {
      if (strpos($route_name, 'dyniva_core.managed_entity') === 0) {
        /*
         * @var \Drupal\dyniva_core\Entity\ManagedEntity $managed_entity
         */
        $managed_entity = $this->routeMatch->getParameter('managed_entity');
        if ($managed_entity->get('entity_type') == 'node' && count($urls) == 1) {
          $urls[] = $this->createUrlFromRelativeUri('/manage/content');
        }
        try {
          $manage_route = "view.manage_{$managed_entity->id()}.page_list";
          /* @var \Drupal\Core\Routing\RouteProviderInterface $route_provider */
          $route_provider = \Drupal::service('router.route_provider');
          $r = $route_provider->getRouteByName($manage_route);
          $url = Url::fromRoute($manage_route);
          if ($url->isRouted()) {
            $urls[] = $url;
            $tasks = $localTaskManager->getLocalTasks($url->getRouteName());
            if (!empty($tasks)) {
              foreach ($tasks['tabs'] as $key => $item) {
                if ($item['#link']['url']->getRouteName() != $url->getRouteName()) {
                  $urls[] = $item['#link']['url'];
                }
              }
            }
          }
        }
        catch (RouteNotFoundException $e) {

        }
      }
    }

    $url = $this->createUrlFromRelativeUri('/' . $path);
    if ($url->isRouted()) {
      $tasks = $localTaskManager->getLocalTasks($url->getRouteName());
      if (!empty($tasks)) {
        foreach ($tasks['tabs'] as $key => $item) {
          if ($item['#link']['url']->getRouteName() != $url->getRouteName()) {
            $urls[] = $item['#link']['url'];
          }
        }
      }
    }
    \Drupal::moduleHandler()->alter('dyniva_core_current_path_urls', $urls);
    return array_reverse($urls);
  }

  /**
   * Create a Url Object from a relative uri (e.g. /news/drupal8-release-party)
   *
   * @param string $relativeUri
   *   Relative uri.
   *
   * @return \Drupal\Core\Url
   *   Url.
   */
  protected function createUrlFromRelativeUri($relativeUri) {
    // @see https://www.drupal.org/node/2810961
    if (UrlHelper::isExternal(substr($relativeUri, 1))) {
      return Url::fromUri('base:' . $relativeUri);
    }

    if ((strpos($relativeUri, '/') !== 0) && (strpos($relativeUri, '#') !== 0) && (strpos($relativeUri, '?') !== 0)) {
      $relativeUri = '/' . $relativeUri;
    }
    return Url::fromUserInput($relativeUri);
  }

}
