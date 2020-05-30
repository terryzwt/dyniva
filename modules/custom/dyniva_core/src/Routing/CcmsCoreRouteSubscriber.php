<?php

namespace Drupal\dyniva_core\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\dyniva_core\Entity\ManagedEntity;
use Symfony\Component\Routing\Route;
use Drupal\dyniva_core\Plugin\ManagedEntityPluginManager;
use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\Component\Utility\Html;
use Drupal\Core\Config\Entity\ConfigEntityType;

/**
 * Class CcmsCoreRouteSubscriber.
 *
 * @package Drupal\dyniva_core\Routing
 *          Listens to the dynamic route events.
 */
class CcmsCoreRouteSubscriber extends RouteSubscriberBase {
  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The plugin manager.
   *
   * @var \Drupal\dyniva_core\Plugin\ManagedEntityPluginManager
   */
  protected $managedEntityPluginManager;

  /**
   * Constructs a new RouteSubscriber object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_manager
   *   The entity type manager.
   * @param \Drupal\dyniva_core\Plugin\ManagedEntityPluginManager $managedEntityPluginManager
   *   The plugin manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_manager, ManagedEntityPluginManager $managedEntityPluginManager) {
    $this->entityTypeManager = $entity_manager;
    $this->managedEntityPluginManager = $managedEntityPluginManager;
  }

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    $managedEntitys = $this->entityTypeManager->getStorage('managed_entity')->loadMultiple();
    $plugins = $this->managedEntityPluginManager->getDefinitions();
    foreach ($managedEntitys as $entity) {
      
      if($entityType = $this->entityTypeManager->getDefinition($entity->getManagedEntityType())){
        if($entityType instanceof  ConfigEntityType && $entityType->hasListBuilderClass()) {
          $route = new Route("/manage/{$entity->getPath()}");
          $route
          ->addDefaults([
            '_entity_list' => $entityType->id(),
            '_title' => $entity->label(),
            '_title_context' => ['managed_entity_label'],
          ])
          ->setRequirement('_permission', "manage ccms {$entity->id()}");
          $collection->add("dyniva_core.managed_entity.{$entity->id()}.list_page", $route);
        }
      }
      if ($route = $this->getManagedEntityAddRoute($entity)) {
        $collection->add("dyniva_core.managed_entity.{$entity->id()}.add_page", $route);
      }
      if ($route = $this->getManagedEntityEditRoute($entity)) {
        $collection->add("dyniva_core.managed_entity.{$entity->id()}.edit_page", $route);
      }
      if ($route = $this->getManagedEntityDeleteRoute($entity)) {
        $collection->add("dyniva_core.managed_entity.{$entity->id()}.delete_page", $route);
      }
      if ($route = $this->getManagedEntityImportRoute($entity)) {
        $collection->add("dyniva_core.managed_entity.{$entity->id()}.import_page", $route);
      }

      foreach ($plugins as $p) {
        if ($this->managedEntityPluginManager->isPluginEnable($entity, $p['id'])) {
          if ($route = $this->getManagedEntityPluginRoute($entity, $p)) {
            $collection->add("dyniva_core.managed_entity.{$entity->id()}.{$p['id']}_page", $route);
          }
          if ($p['id'] == 'moderation' &&  $route = $this->getManagedEntityPublishRoute($entity)) {
            $collection->add("dyniva_core.managed_entity.{$entity->id()}.publish_page", $route);
          }
          if ($p['id'] == 'translation' && \Drupal::moduleHandler()->moduleExists('content_translation')) {
            $entity_type_id = $entity->getManagedEntityType();
            $path = "/manage/{$entity->getPath()}/{managed_entity_id}/translation";

            $route = new Route(
                $path . '/add/{source}/{target}',
                [
                  '_controller' => '\Drupal\dyniva_core\Controller\ContentTranslationController::add',
                  'source' => NULL,
                  'target' => NULL,
                  '_title' => 'Add',
                  'managed_entity' => $entity->id(),
                ],
                [
                  '_permission' => "manage ccms {$entity->id()} translation",
                ],
                [
                  'parameters' => [
                    'source' => [
                      'type' => 'language',
                    ],
                    'target' => [
                      'type' => 'language',
                    ],
                    'managed_entity_id' => ['type' => 'managed_entity_id', 'load_latest_revision' => TRUE],
                    'managed_entity' => ['type' => 'entity:managed_entity'],
                  ],
                ]
                );
            $collection->add("dyniva_core.managed_entity.{$entity->id()}.translation_add", $route);

            $route = new Route(
                $path . '/edit/{language}',
                [
                  '_controller' => '\Drupal\dyniva_core\Controller\ContentTranslationController::edit',
                  'language' => NULL,
                  '_title' => 'Edit',
                  'managed_entity' => $entity->id(),
                ],
                [
                  '_permission' => "manage ccms {$entity->id()} translation",
                ],
                [
                  'parameters' => [
                    'language' => [
                      'type' => 'language',
                    ],
                    'managed_entity_id' => ['type' => 'managed_entity_id', 'load_latest_revision' => TRUE],
                    'managed_entity' => ['type' => 'entity:managed_entity'],
                  ],
                ]
                );
            $collection->add("dyniva_core.managed_entity.{$entity->id()}.translation_edit", $route);

            $route = new Route(
                $path . '/delete/{language}',
                [
                  '_form' => '\Drupal\dyniva_core\Form\ContentTranslationDeleteForm',
                  'language' => NULL,
                  '_title' => 'Delete',
                  'managed_entity' => $entity->id(),
                ],
                [
                  '_permission' => "manage ccms {$entity->id()} translation",
                ],
                [
                  'parameters' => [
                    'language' => [
                      'type' => 'language',
                    ],
                    'managed_entity_id' => ['type' => 'managed_entity_id', 'load_latest_revision' => TRUE],
                    'managed_entity' => ['type' => 'entity:managed_entity'],
                  ],
                ]
                );
            $collection->add("dyniva_core.managed_entity.{$entity->id()}.translation_delete", $route);
          }
        }
      }
    }
    $vocabulary = Vocabulary::loadMultiple();
    foreach ($vocabulary as $v) {
      if ($route = $this->getVocabularyRoute($v)) {
        $collection->add("dyniva_core.manage_taxonomy.{$v->id()}", $route);
      }
    }
  }

  /**
   * Get vocabulary route.
   *
   * @param \Drupal\taxonomy\Entity\Vocabulary $entity
   *   The vocabulary entity.
   *
   * @return \Symfony\Component\Routing\Route
   *   Route entity.
   */
  protected function getVocabularyRoute(Vocabulary $entity) {
    $id = $entity->id();
    $path = Html::getId($id);
    $route = new Route("/manage/{$path}");
    $route->addDefaults([
      '_controller' => '\Drupal\dyniva_core\Controller\CcmsCoreController::manageTaxonomy',
      '_title_callback' => '\Drupal\dyniva_core\Controller\CcmsCoreController::manageTaxonomyTitle',
      'vid' => $id,
    ])->setRequirement('_permission', "manage ccms {$id}");
    return $route;
  }

  /**
   * Get add route.
   *
   * @param \Drupal\dyniva_core\Entity\ManagedEntity $entity
   *   The entity.
   *
   * @return \Symfony\Component\Routing\Route
   *   The route.
   */
  protected function getManagedEntityAddRoute(ManagedEntity $entity) {
    $id = $entity->id();
    $route = new Route("/manage/{$entity->getPath()}/add");
    $route->addDefaults([
      '_controller' => '\Drupal\dyniva_core\Controller\CcmsCoreManagedEntityController::entityAdd',
      '_title_callback' => '\Drupal\dyniva_core\Controller\CcmsCoreManagedEntityController::addPageTitle',
      'managed_entity' => $id,
      'permission' => "add ccms {$entity->id()}",
    ])->setRequirement('_custom_access', "Drupal\dyniva_core\CcmsCoreEntityModerateAccess::permissionAccess");
    return $route;
  }

  /**
   * Get view route.
   *
   * @param \Drupal\dyniva_core\Entity\ManagedEntity $entity
   *   The entity.
   *
   * @return \Symfony\Component\Routing\Route
   *   The route.
   */
  protected function getManagedEntityViewRoute(ManagedEntity $entity) {
    $id = $entity->id();
    $route = new Route("/manage/{$entity->getPath()}/{managed_entity_id}");
    $route->addDefaults([
      '_controller' => '\Drupal\dyniva_core\Controller\CcmsCoreManagedEntityController::entityView',
      '_title_callback' => '\Drupal\dyniva_core\Controller\CcmsCoreManagedEntityController::viewPageTitle',
      'managed_entity' => $id,
    ])->setRequirement('_permission', "view ccms {$entity->id()}")
    ->setOption('parameters', ['managed_entity_id' => ['type' => 'managed_entity_id', 'load_latest_revision' => TRUE]]);

    return $route;
  }

  /**
   * Get edit route.
   *
   * @param \Drupal\dyniva_core\Entity\ManagedEntity $entity
   *   The entity.
   *
   * @return \Symfony\Component\Routing\Route
   *   The route.
   */
  protected function getManagedEntityEditRoute(ManagedEntity $entity) {
    $id = $entity->id();
    $route = new Route("/manage/{$entity->getPath()}/{managed_entity_id}/edit");
    $route->addDefaults([
      '_controller' => '\Drupal\dyniva_core\Controller\CcmsCoreManagedEntityController::entityEdit',
      '_title_callback' => '\Drupal\dyniva_core\Controller\CcmsCoreManagedEntityController::editPageTitle',
      'managed_entity' => $id,
      'op' => 'edit',
    ])->setRequirement('_custom_access', "Drupal\dyniva_core\CcmsCoreEntityModerateAccess::entityAccess")
    ->setOption('parameters', ['managed_entity_id' => ['type' => 'managed_entity_id', 'load_latest_revision' => TRUE]]);

    return $route;
  }

  /**
   * Get delete route.
   *
   * @param \Drupal\dyniva_core\Entity\ManagedEntity $entity
   *   The entity.
   *
   * @return \Symfony\Component\Routing\Route
   *   The route.
   */
  protected function getManagedEntityDeleteRoute(ManagedEntity $entity) {
    $id = $entity->id();
    $route = new Route("/manage/{$entity->getPath()}/{managed_entity_id}/delete");
    $route->addDefaults([
      '_controller' => '\Drupal\dyniva_core\Controller\CcmsCoreManagedEntityController::entityDelete',
      '_title_callback' => '\Drupal\dyniva_core\Controller\CcmsCoreManagedEntityController::deletePageTitle',
      'managed_entity' => $id,
      'op' => 'delete',
    ])->setRequirement('_custom_access', "Drupal\dyniva_core\CcmsCoreEntityModerateAccess::entityAccess")
    ->setOption('parameters', ['managed_entity_id' => ['type' => 'managed_entity_id', 'load_latest_revision' => TRUE]]);

    return $route;
  }

  /**
   * Get publish route.
   *
   * @param \Drupal\dyniva_core\Entity\ManagedEntity $entity
   *   The entity.
   *
   * @return \Symfony\Component\Routing\Route
   *   The route.
   */
  protected function getManagedEntityPublishRoute(ManagedEntity $entity) {
    $id = $entity->id();
    $route = new Route("/manage/{$entity->getPath()}/{managed_entity_id}/publish");
    $route->addDefaults([
      '_form' => '\Drupal\dyniva_core\Form\ManagedEntityPublishForm',
      '_title_callback' => '\Drupal\dyniva_core\Controller\CcmsCoreManagedEntityController::publishPageTitle',
      'managed_entity' => $id,
    ])->setRequirement('_permission', "manage ccms {$entity->id()} moderation")
      ->setOption('parameters', [
        'managed_entity_id' => ['type' => 'managed_entity_id', 'load_latest_revision' => TRUE],
        'managed_entity' => ['type' => 'entity:managed_entity'],

      ]);

    return $route;
  }

  /**
   * Get route by plugin.
   *
   * @param \Drupal\dyniva_core\Entity\ManagedEntity $entity
   *   The entity.
   * @param array $plugin_definition
   *   The plugin definition.
   *
   * @return \Symfony\Component\Routing\Route|bool
   *   The route.
   */
  protected function getManagedEntityPluginRoute(ManagedEntity $entity, array $plugin_definition) {
    $id = $entity->id();
    if (!empty($plugin_definition['class'])) {
      $instance = $this->managedEntityPluginManager->createInstance($plugin_definition['id']);
      $path = $instance->getPagePath($entity);
      $requirements = $instance->getPageRequirements($entity);
      if (!empty($path)) {
        $route = new Route("/manage/{$entity->getPath()}/{managed_entity_id}/{$path}");
        $route->addDefaults([
          '_controller' => '\Drupal\dyniva_core\Controller\CcmsCoreManagedEntityController::pluginPage',
          '_title_callback' => '\Drupal\dyniva_core\Controller\CcmsCoreManagedEntityController::pluginPageTitle',
          'managed_entity' => $id,
          'plugin_id' => $plugin_definition['id'],
        ])
          ->setOption('parameters', [
            'managed_entity_id' => ['type' => 'managed_entity_id', 'load_latest_revision' => TRUE],
            'managed_entity' => ['type' => 'entity:managed_entity'],

          ]);
        if (!empty($requirements)) {
          foreach ($requirements as $k => $v) {
            $route->setRequirement($k, $v);
          }
        }

        return $route;
      }
    }
    return FALSE;
  }

  /**
   * Get import route.
   *
   * @param \Drupal\dyniva_core\Entity\ManagedEntity $entity
   *   The entity.
   *
   * @return \Symfony\Component\Routing\Route
   *   The route.
   */
  protected function getManagedEntityImportRoute(ManagedEntity $entity) {
    $id = $entity->id();
    $className = ucfirst($entity->id());
    $importFormClass = "\Drupal\\{$entity->get('module_name')}\Form\\{$className}ImportForm";
    if (class_exists($importFormClass)) {
      $route = new Route("/manage/{$entity->getPath()}/import");
      $route->addDefaults([
        '_form' => $importFormClass,
        '_title_callback' => '\Drupal\dyniva_core\Controller\CcmsCoreManagedEntityController::importPageTitle',
        'managed_entity' => $id,
      ])->setRequirement('_permission', "import ccms {$entity->id()}")
        ->setOption('parameters', [
          'managed_entity' => ['type' => 'entity:managed_entity'],
        ]);

      return $route;
    }
    return FALSE;
  }

}
