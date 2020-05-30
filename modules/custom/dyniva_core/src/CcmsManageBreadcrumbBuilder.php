<?php

namespace Drupal\dyniva_core;

use Drupal\Core\Access\AccessManagerInterface;
use Drupal\Core\Breadcrumb\Breadcrumb;
use Drupal\Core\Breadcrumb\BreadcrumbBuilderInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Controller\TitleResolverInterface;
use Drupal\Core\Link;
use Drupal\Core\ParamConverter\ParamNotConvertedException;
use Drupal\Core\Path\CurrentPathStack;
use Drupal\Core\PathProcessor\InboundPathProcessorInterface;
use Drupal\Core\Routing\RequestContext;
use Drupal\Core\Routing\RouteMatch;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Routing\Exception\MethodNotAllowedException;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Routing\Matcher\RequestMatcherInterface;
use Drupal\Core\Menu\MenuLinkManager;
use Drupal\Core\Menu\MenuActiveTrailInterface;
use Symfony\Component\Validator\Constraints\Count;

/**
 * Class to define the menu_link breadcrumb builder.
 */
class CcmsManageBreadcrumbBuilder implements BreadcrumbBuilderInterface {
  use StringTranslationTrait;

  /**
   * The router request context.
   *
   * @var \Drupal\Core\Routing\RequestContext
   */
  protected $context;

  /**
   * The menu link access service.
   *
   * @var \Drupal\Core\Access\AccessManagerInterface
   */
  protected $accessManager;

  /**
   * The dynamic router service.
   *
   * @var \Symfony\Component\Routing\Matcher\RequestMatcherInterface
   */
  protected $router;

  /**
   * The dynamic router service.
   *
   * @var \Drupal\Core\PathProcessor\InboundPathProcessorInterface
   */
  protected $pathProcessor;

  /**
   * Site config object.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected $siteConfig;

  /**
   * The title resolver.
   *
   * @var \Drupal\Core\Controller\TitleResolverInterface
   */
  protected $titleResolver;

  /**
   * The current user object.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The menu link manager.
   *
   * @var \Drupal\Core\Menu\MenuLinkManager
   */
  protected $menuLinkManager;

  /**
   * The menu active trail.
   *
   * @var \Drupal\Core\Menu\MenuActiveTrailInterface
   */
  protected $menuActiveTrail;

  /**
   * Constructs the PathBasedBreadcrumbBuilder.
   *
   * @param \Drupal\Core\Routing\RequestContext $context
   *   The router request context.
   * @param \Drupal\Core\Access\AccessManagerInterface $access_manager
   *   The menu link access service.
   * @param \Symfony\Component\Routing\Matcher\RequestMatcherInterface $router
   *   The dynamic router service.
   * @param \Drupal\Core\PathProcessor\InboundPathProcessorInterface $path_processor
   *   The inbound path processor.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory service.
   * @param \Drupal\Core\Controller\TitleResolverInterface $title_resolver
   *   The title resolver service.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user object.
   * @param \Drupal\Core\Path\CurrentPathStack $current_path
   *   The current path.
   * @param \Drupal\Core\Menu\MenuLinkManager $menu_link_manager
   *   The menu link manager.
   * @param \Drupal\Core\Menu\MenuActiveTrailInterface $menuActiveTrail
   *   The menu active trail.
   */
  public function __construct(RequestContext $context, AccessManagerInterface $access_manager, RequestMatcherInterface $router, InboundPathProcessorInterface $path_processor, ConfigFactoryInterface $config_factory, TitleResolverInterface $title_resolver, AccountInterface $current_user, CurrentPathStack $current_path, MenuLinkManager $menu_link_manager, MenuActiveTrailInterface $menuActiveTrail) {
    $this->context = $context;
    $this->accessManager = $access_manager;
    $this->router = $router;
    $this->pathProcessor = $path_processor;
    $this->siteConfig = $config_factory->get('system.site');
    $this->titleResolver = $title_resolver;
    $this->currentUser = $current_user;
    $this->currentPath = $current_path;
    $this->menuLinkManager = $menu_link_manager;
    $this->menuActiveTrail = $menuActiveTrail;
  }

  /**
   * {@inheritdoc}
   */
  public function applies(RouteMatchInterface $route_match) {
    return \Drupal::theme()->getActiveTheme()->getName() == 'dyniva_admin';
  }

  /**
   * {@inheritdoc}
   */
  public function build(RouteMatchInterface $route_match) {
    $breadcrumb = new Breadcrumb();
    $links = [];
    $curr_lang = \Drupal::languageManager()->getCurrentLanguage()->getId();

    // General path-based breadcrumbs. Use the actual request path, prior to
    // resolving path aliases, so the breadcrumb can be defined by simply
    // creating a hierarchy of path aliases.
    $ids = $this->menuActiveTrail->getActiveTrailIds('manage');
    $front = $this->siteConfig->get('page.front');
    array_pop($ids);
    array_pop($ids);

    // Because this breadcrumb builder is path and config based, vary cache
    // by the 'url.path' cache context and config changes.
    $breadcrumb->addCacheContexts(['url.path']);

    $cur_route = Url::fromRouteMatch(\Drupal::routeMatch());
    $curr_title = $this->titleResolver->getTitle(\Drupal::request(), \Drupal::routeMatch()->getRouteObject());
    if (is_object($curr_title)) {
      $curr_title = (string) $curr_title;
    }
    $links[] = Link::createFromRoute($curr_title, $cur_route->getRouteName(), $cur_route->getRouteParameters());
    while (count($ids) > 0) {
      $link_id = array_shift($ids);
      if (!empty($link_id)) {
        $menu_link = $this->menuLinkManager->getDefinition($link_id);
        $route_name = $menu_link['route_name'];
        if ($route_name == '<nolink>') {
          $url = Url::fromRoute('<nolink>');
          $links[] = new Link($menu_link['title'], $url);
        }
        else {
          $access = $this->accessManager->checkNamedRoute($route_name, $cur_route->getRouteParameters(), $this->currentUser, TRUE);
          // The set of breadcrumb links depends on the access result, so merge
          // the access result's cacheability metadata.
          if ($access->isAllowed()) {
            $menu_links = $this->menuLinkManager->loadLinksByRoute($route_name, $cur_route->getRouteParameters());
            if (empty($menu_links)) {
              $menu_links = $this->menuLinkManager->loadLinksByRoute($route_name);
            }
            $title = "";
            if (!empty($menu_links)) {
              $ml = end($menu_links);
              $title = $ml->getTitle();
            }

            // Add a linked breadcrumb unless it's the current page.
            $url = Url::fromRoute($route_name, $cur_route->getRouteParameters());
            $links[] = new Link($title, $url);
          }
        }

      }
    }

    // $links[] = Link::createFromRoute('Home', '<front>');.
    $links = array_reverse($links);

    $links = $this->removeRepeatedSegments($links);

    if (count($links) == 1) {
      $links = [];
    }
    else {
      $last = array_pop($links);
      $last->setUrl(Url::fromRoute('<none>'));
      array_push($links, $last);
    }

    return $breadcrumb->setLinks($links);
  }

  /**
   * Remove duplicate repeated segments.
   *
   * @param \Drupal\Core\Link[] $links
   *   The links.
   *
   * @return \Drupal\Core\Link[]
   *   The new links.
   */
  protected function removeRepeatedSegments(array $links) {
    $newLinks = [];

    /** @var \Drupal\Core\Link $last */
    $last = NULL;

    foreach ($links as $link) {
      if (empty($last) || (!$this->linksAreEqual($last, $link))) {
        $newLinks[] = $link;
      }

      $last = $link;
    }

    return $newLinks;
  }

  /**
   * Compares two breadcrumb links for equality.
   *
   * @param \Drupal\Core\Link $link1
   *   The first link.
   * @param \Drupal\Core\Link $link2
   *   The second link.
   *
   * @return bool
   *   TRUE if equal, FALSE otherwise.
   */
  protected function linksAreEqual(Link $link1, Link $link2) {
    $links_equal = TRUE;

    try {
      if ($link1->getUrl()->getInternalPath() != $link2->getUrl()->getInternalPath()) {
        $links_equal = FALSE;
      }
    }
    catch (Exception $e) {
      return TRUE;
    }

    return $links_equal;
  }

  /**
   * Matches a path in the router.
   *
   * @param string $path
   *   The request path with a leading slash.
   * @param array $exclude
   *   An array of paths or system paths to skip.
   *
   * @return \Symfony\Component\HttpFoundation\Request
   *   A populated request object or NULL if the path couldn't be matched.
   */
  protected function getRequestForPath($path, array $exclude) {
    if (!empty($exclude[$path])) {
      return NULL;
    }
    // @todo Use the RequestHelper once https://www.drupal.org/node/2090293 is
    //   fixed.
    $request = Request::create($path);
    // Performance optimization: set a short accept header to reduce overhead in
    // AcceptHeaderMatcher when matching the request.
    $request->headers->set('Accept', 'text/html');
    // Find the system path by resolving aliases, language prefix, etc.
    $processed = $this->pathProcessor->processInbound($path, $request);
    if (empty($processed) || !empty($exclude[$processed])) {
      // This resolves to the front page, which we already add.
      return NULL;
    }
    $this->currentPath->setPath($processed, $request);
    // Attempt to match this path to provide a fully built request.
    try {
      $request->attributes->add($this->router->matchRequest($request));
      return $request;
    }
    catch (ParamNotConvertedException $e) {
      return NULL;
    }
    catch (ResourceNotFoundException $e) {
      return NULL;
    }
    catch (MethodNotAllowedException $e) {
      return NULL;
    }
    catch (AccessDeniedHttpException $e) {
      return NULL;
    }
  }

}
