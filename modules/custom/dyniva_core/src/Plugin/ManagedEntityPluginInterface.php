<?php

namespace Drupal\dyniva_core\Plugin;

use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\dyniva_core\Entity\ManagedEntity;
use Drupal\Core\Entity\EntityInterface;

/**
 * Defines an interface for Managed entity plugin plugins.
 */
interface ManagedEntityPluginInterface extends PluginInspectionInterface {

  /**
   * 获取页面内容.
   */
  public function buildPage(ManagedEntity $managedEntity, EntityInterface $entity);

  /**
   * 获取页面标题.
   */
  public function getPageTitle(ManagedEntity $managedEntity, EntityInterface $entity);

  /**
   * 获取页面路径.
   */
  public function getPagePath(ManagedEntity $managedEntity);

  /**
   * 获取页面权限.
   */
  public function getPageRequirements(ManagedEntity $managedEntity);

  /**
   * 修改 operation links.
   */
  public function applyOperationLinks(ManagedEntity $managedEntity, EntityInterface $entity, &$operations);

  /**
   * 是否添加 menu tab.
   */
  public function isMenuTask(ManagedEntity $managedEntity);

  /**
   * 是否添加 menu action.
   */
  public function isMenuAction(ManagedEntity $managedEntity);

}
