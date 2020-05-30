<?php
namespace Drupal\dyniva_core\Plugin\ManagedEntity;

use Drupal\dyniva_core\Plugin\ManagedEntityPluginBase;
use Drupal\dyniva_core\Entity\ManagedEntity;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Url;
use Drupal\content_translation\ContentTranslationManager;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Language\LanguageInterface;

/**
 * ManagedEntity Plugin.
 *
 * @ManagedEntityPlugin(
 *  id = "entity_clone",
 *  label = @Translation("Clone"),
 *  weight = 0
 * )
 *
 */
class EntityClone extends ManagedEntityPluginBase{
  /**
   * @inheritdoc
   */
  public function buildPage(ManagedEntity $managedEntity, EntityInterface $entity){
    $form = \Drupal::formBuilder()->getForm('\Drupal\dyniva_core\Form\EntityCloneConfirmForm', $managedEntity, $entity);
    return $form;
  }
  /**
   * @inheritdoc
   */
  public function getPageTitle(ManagedEntity $managedEntity, EntityInterface $entity){
    return $this->pluginDefinition['label'] . ' ' . $entity->label();
  }
  /**
   * @inheritdoc
   */
  public function isMenuTask(ManagedEntity $managedEntity){
    return TRUE;
  }
  /**
   * @inheritdoc
   */
  public function isMenuAction(ManagedEntity $managedEntity){
    return FALSE;
  }
}
