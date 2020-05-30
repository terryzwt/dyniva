<?php

namespace Drupal\dyniva_media\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Drupal\Core\Routing\RouteProviderInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Routing\RouteProvider;

/**
 * Provides local action definitions for all entity bundles.
 */
class BulkUploadLocalAction extends DeriverBase implements ContainerDeriverInterface {

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
   * Constructs a FormModeManagerLocalAction object.
   *
   * @param \Drupal\Core\Routing\RouteProviderInterface $route_provider
   *   The route provider to load routes by name.
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager.
   */
  public function __construct(RouteProviderInterface $route_provider, EntityTypeManagerInterface $entity_type_manager, AccountInterface $account) {
    $this->routeProvider = $route_provider;
    $this->entityTypeManager = $entity_type_manager;
    $this->account = $account;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id) {
    return new static(
        $container->get('router.route_provider'),
        $container->get('entity_type.manager'),
        $container->get('current_user')
        );
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    $this->derivatives = [];
    $managedEntitys = $this->entityTypeManager->getStorage('managed_entity')->loadByProperties(['entity_type' => 'media']);
    foreach ($managedEntitys as $entity) {
      $id = $entity->id();
      $label = $entity->label();
      if(in_array($entity->getBundle(), ['video', 'video_file'])) continue;

      $this->derivatives["dyniva_media.managed_entity.{$id}.bulk_upload"] = [
        'route_name' => "dyniva_media.managed_entity.{$id}.bulk_add_page",
        'route_parameters' => [
          'bundle' => $entity->getBundle(),
        ],
        'title' => $this->t('Bulk add @entity_label', [
          '@entity_label' => $label,
        ]),
        'appears_on' => [
          "view.manage_{$id}.page_list",
        ],
      ];
    }
    return $this->derivatives;
  }

}
