<?php

namespace Drupal\dyniva_core\ParamConverter;

use Symfony\Component\Routing\Route;
use Drupal\Core\ParamConverter\ParamConverterInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\RevisionableInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\dyniva_core\TransliterationHelper;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\TranslatableInterface;

/**
 * Provides upcasting for a node entity in preview.
 */
class CcmsCoreParamConverter implements ParamConverterInterface {

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new RouteSubscriber object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_manager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_manager) {
    $this->entityTypeManager = $entity_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function convert($value, $definition, $name, array $defaults) {
    $mid = isset($defaults['managed_entity'])?$defaults['managed_entity']:false;
    if(!$mid){
      $path = \Drupal::request()->getpathInfo();
      $arg = explode('/',$path);
      $mid = $arg[1];
    }
    $managed_entity = $this->entityTypeManager->getStorage('managed_entity')->load($mid);
    if($managed_entity){
      $entity_definition = $this->entityTypeManager->getDefinition($managed_entity->get('entity_type'));
      $entity = $this->entityTypeManager->getStorage($managed_entity->get('entity_type'))->load($value);
      // If the entity type is revisionable and the parameter has the
      // "load_latest_revision" flag, load the latest revision.
      if ($entity instanceof RevisionableInterface && !empty($definition['load_latest_revision']) && $entity_definition->isRevisionable()) {
        // Retrieve the latest revision ID taking translations into account.
        $langcode = \Drupal::languageManager()
        ->getCurrentLanguage(LanguageInterface::TYPE_CONTENT)
        ->getId();
        $entity = TransliterationHelper::getLatestTranslationAffectedRevision($entity, $langcode);
      }
      
      // If the entity type is translatable, ensure we return the proper
      // translation object for the current context.
      if ($entity instanceof EntityInterface && $entity instanceof TranslatableInterface) {
        $entity = \Drupal::entityManager()->getTranslationFromContext($entity, NULL, ['operation' => 'entity_upcast']);
      }
      return $entity;
    }
    return false;
  }

  /**
   * {@inheritdoc}
   */
  public function applies($definition, $name, Route $route) {
    if (!empty($definition['type']) && $definition['type'] == 'managed_entity_id') {
      return TRUE;
    }
    return FALSE;
  }

}
