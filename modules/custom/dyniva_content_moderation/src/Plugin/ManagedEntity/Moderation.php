<?php
namespace Drupal\dyniva_content_moderation\Plugin\ManagedEntity;

use Drupal\dyniva_core\Plugin\ManagedEntityPluginBase;
use Drupal\dyniva_core\Entity\ManagedEntity;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Url;
use Drupal\Core\Language\LanguageInterface;

/**
 * ManagedEntity Plugin.
 *
 * @ManagedEntityPlugin(
 *  id = "moderation",
 *  label = @Translation("Moderation"),
 *  weight = 1
 * )
 *
 */
class Moderation extends ManagedEntityPluginBase{
  /**
   * @inheritdoc
   */
  public function buildPage(ManagedEntity $managedEntity, EntityInterface $entity){
    if($entity->getEntityTypeId() == 'node'){
      $form = \Drupal::formBuilder()->getForm('\Drupal\dyniva_content_moderation\Form\NodeModerateForm',$entity);
    }else{
      $form = \Drupal::formBuilder()->getForm('\Drupal\dyniva_content_moderation\Form\EntityModerateForm',$entity);
    }
    return $form;
  }
  /**
   * @inheritdoc
   */
  public function getPageTitle(ManagedEntity $managedEntity, EntityInterface $entity){
    return $entity->label() . ' ' . $this->pluginDefinition['label'];
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
  
  /**
   * @inheritdoc
   */
  public function applyOperationLinks(ManagedEntity $managedEntity, EntityInterface $entity, &$operations, LanguageInterface $language = NULL){
    $url = Url::fromRoute("dyniva_core.managed_entity.{$managedEntity->id()}.{$this->getPluginId()}_page",['managed_entity_id' =>$entity->id() ],['language' => $language]);
    $account = \Drupal::currentUser();
    $access = $account->hasPermission("manage ccms {$managedEntity->id()} {$this->getPluginId()}");
    if($access){
      $operations[$this->getPluginId()] = [
        'title' => t('Moderate'),
        'url' => $url,
        'weight' => count($operations)
      ];
    }
  
  }
}
