<?php

namespace Drupal\dyniva_core\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Drupal\Core\Routing\RouteProviderInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Routing\RouteProvider;
use Drupal\dyniva_core\Plugin\ManagedEntityPluginManager;

/**
 * Provides local action definitions for all entity bundles.
 */
class ManagedEntityLocalTask extends DeriverBase implements ContainerDeriverInterface {

  use StringTranslationTrait;

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $account;

  /**
   * Managed entity plugin manager.
   *
   * @var \Drupal\dyniva_core\Plugin\ManagedEntityPluginManager
   */
  protected $managedEntityPluginManager;

  /**
   * Construct.
   *
   * @param \Drupal\Core\Routing\RouteProviderInterface $route_provider
   *   The route provider to load routes by name.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity manager.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   Account object.
   * @param \Drupal\dyniva_core\Plugin\ManagedEntityPluginManager $managedEntityPluginManager
   *   Managed entity plugin manager.
   */
  public function __construct(RouteProviderInterface $route_provider, EntityTypeManagerInterface $entity_type_manager, AccountInterface $account, ManagedEntityPluginManager $managedEntityPluginManager) {
    $this->routeProvider = $route_provider;
    $this->entityTypeManager = $entity_type_manager;
    $this->account = $account;
    $this->managedEntityPluginManager = $managedEntityPluginManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id) {
    return new static(
        $container->get('router.route_provider'),
        $container->get('entity_type.manager'),
        $container->get('current_user'),
        $container->get('plugin.manager.managed_entity_plugin')
        );
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    $this->derivatives = [];
    $managedEntitys = $this->entityTypeManager->getStorage('managed_entity')->loadMultiple();
    $plugins = $this->managedEntityPluginManager->getDefinitions();

    $weight = 0;
    foreach ($managedEntitys as $entity) {
      $id = $entity->id();
      $label = $entity->label();
      $base = FALSE;
      foreach ($plugins as $p) {
        if ($this->managedEntityPluginManager->isPluginEnable($entity, $p['id'])) {
          $instance = $this->managedEntityPluginManager->createInstance($p['id']);
          if ($instance->isMenuTask($entity)) {
            if (!$base) {
              $base = "dyniva_core.managed_entity.{$id}.{$p['id']}_page";
            }
            $this->derivatives["dyniva_core.managed_entity.{$id}.{$p['id']}_task"] = [
              'route_name' => "dyniva_core.managed_entity.{$id}.{$p['id']}_page",
              'title' => $p['label'],
              'base_route' => $base,
              'weight' => $weight++,
            ] + $base_plugin_definition;
          }
        }
      }
    }

    return $this->derivatives;
  }

}
