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
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Drupal\dyniva_core\Plugin\ManagedEntityPluginManager;

/**
 * Provides local action definitions for all entity bundles.
 */
class ManagedEntityLocalAction extends DeriverBase implements ContainerDeriverInterface {

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
   * Constructs.
   *
   * @param \Drupal\Core\Routing\RouteProviderInterface $route_provider
   *   The route provider to load routes by name.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   Entity type manager.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   Account objcet.
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
    foreach ($managedEntitys as $entity) {
      $id = $entity->id();
      $label = $entity->label();
      if ($entity->get('standalone')) {
        $this->derivatives["dyniva_core.managed_entity.{$id}.list_add"] = [
          'route_name' => "dyniva_core.managed_entity.{$id}.add_page",
          'title' => $this->t('Add @entity_label', [
            '@entity_label' => $label,
          ]),
          'appears_on' => [
            "view.manage_{$id}.page_list",
            "dyniva_core.managed_entity.{$id}.list_page",
            "dyniva_core.manage_taxonomy.{$entity->getBundle()}",
          ],
          'class' => '\Drupal\dyniva_core\Menu\LocalAction',
        ];
        if ($entity->get('has_draft')) {
          $this->derivatives["dyniva_core.managed_entity.{$id}.draft_add"] = [
            'route_name' => "dyniva_core.managed_entity.{$id}.add_page",
            'title' => $this->t('Add @entity_label', [
              '@entity_label' => $label,
            ]),
            'appears_on' => [
              "view.manage_{$id}.page_draft",
            ],
            'class' => '\Drupal\dyniva_core\Menu\LocalAction',
          ];
        }

        $importRouteName = "dyniva_core.managed_entity.{$id}.import_page";
        try {
          if (\Drupal::service('router.route_provider')->getRouteByName($importRouteName)) {
            $this->derivatives["dyniva_core.managed_entity.{$id}.list_import"] = [
              'route_name' => "dyniva_core.managed_entity.{$id}.import_page",
              'title' => $this->t('Import @entity_label', [
                '@entity_label' => $label,
              ]),
              'appears_on' => [
                "view.manage_{$id}.page_list",
                "dyniva_core.managed_entity.{$id}.list_page",
              ],
              'class' => '\Drupal\dyniva_core\Menu\LocalAction',
            ];
          }
        }
        catch (RouteNotFoundException $e) {
        }
      }
      foreach ($plugins as $p) {
        if ($this->managedEntityPluginManager->isPluginEnable($entity, $p['id'])) {
          $instance = $this->managedEntityPluginManager->createInstance($p['id']);
          if ($instance->isMenuAction($entity)) {
            $this->derivatives["dyniva_core.managed_entity.{$id}.list_{$p['id']}"] = [
              'route_name' => "dyniva_core.managed_entity.{$id}.{$p['id']}_page",
              'title' => $this->t('Import @entity_label', [
                '@entity_label' => $label,
              ]),
              'appears_on' => [
                "view.manage_{$id}.page_list",
                "dyniva_core.managed_entity.{$id}.list_page",
              ],
              'class' => '\Drupal\dyniva_core\Menu\LocalAction',
            ];
          }
        }
      }
    }
    return $this->derivatives;
  }

}
