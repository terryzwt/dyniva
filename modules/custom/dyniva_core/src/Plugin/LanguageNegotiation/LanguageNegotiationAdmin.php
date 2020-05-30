<?php

namespace Drupal\dyniva_core\Plugin\LanguageNegotiation;

use Drupal\Core\PathProcessor\PathProcessorManager;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Routing\StackedRouteMatchInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Routing\Matcher\UrlMatcherInterface;
use Drupal\user\Plugin\LanguageNegotiation\LanguageNegotiationUserAdmin;

/**
 * Identifies admin language from the user preferences.
 *
 * @LanguageNegotiation(
 *   id = Drupal\dyniva_core\Plugin\LanguageNegotiation\LanguageNegotiationAdmin::METHOD_ID,
 *   types = {Drupal\Core\Language\LanguageInterface::TYPE_INTERFACE},
 *   weight = -10,
 *   name = @Translation("Dyniva administration pages(with account administration pages)"),
 *   description = @Translation("")
 * )
 */
class LanguageNegotiationAdmin extends LanguageNegotiationUserAdmin implements ContainerFactoryPluginInterface {

  /**
   * The language negotiation method id.
   */
  const METHOD_ID = 'language-dyniva-admin';

  /**
   * Construct.
   *
   * @param \Symfony\Component\Routing\Matcher\UrlMatcherInterface $router
   *   Router.
   * @param \Drupal\Core\PathProcessor\PathProcessorManager $path_processor_manager
   *   Path processor manager.
   * @param \Drupal\Core\Routing\StackedRouteMatchInterface $stacked_route_match
   *   Router match.
   */
  public function __construct(UrlMatcherInterface $router, PathProcessorManager $path_processor_manager, StackedRouteMatchInterface $stacked_route_match) {
    $this->router = $router;
    $this->pathProcessorManager = $path_processor_manager;
    $this->stackedRouteMatch = $stacked_route_match;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $container->get('router'),
      $container->get('path_processor_manager'),
      $container->get('current_route_match')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getLangcode(Request $request = NULL) {
    $langcode = NULL;

    if (($preferred_admin_langcode = $this->currentUser->getPreferredAdminLangcode(FALSE)) && $this->isAdminPath($request)) {
      $langcode = $preferred_admin_langcode;
    }

    return $langcode;
  }

  /**
   * Checks whether the given path is an administrative one.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   *
   * @return bool
   *   TRUE if the path is administrative, FALSE otherwise.
   */
  protected function isAdminPath(Request $request) {
    $result = FALSE;
    if ($request) {
      try {
        $path = $this->pathProcessorManager->processInbound(urldecode(rtrim($request->getPathInfo(), '/')), $request);
      }
      catch (ResourceNotFoundException $e) {
        return FALSE;
      }
      catch (AccessDeniedHttpException $e) {
        return FALSE;
      }
      $result = preg_match('/^\/manage\//', $path) && !preg_match('/\/edit$/', $path);
    }
    return $result;
  }

}
