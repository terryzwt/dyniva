<?php

namespace Drupal\dyniva_core;


use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\field\FieldConfigInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\block_content\Plugin\Block\BlockContentBlock;
use Drupal\panels\PanelsDisplayManager;
use Drupal\panelizer\Panelizer;

class EntityCloneHelper {

  public static function cloneEntity(EntityInterface $entity, array $properties = [], array &$already_cloned = []) {
    if (!empty($already_cloned[$entity->getEntityTypeId()][$entity->id()])) {
      return $already_cloned[$entity->getEntityTypeId()][$entity->id()];
    }
    // Clone referenced entities.
    $cloned_entity = $entity->createDuplicate();
    $already_cloned[$entity->getEntityTypeId()][$entity->id()] = $cloned_entity;
    if ($entity instanceof FieldableEntityInterface) {
      foreach ($cloned_entity->getFieldDefinitions() as $field_id => $field_definition) {
        if (self::fieldIsClonable($field_definition)) {
          $field = $entity->get($field_id);
          /** @var \Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem $value */
          if ($field->count() > 0) {
            $cloned_entity->set($field_id, self::cloneField($field, $field_definition, $properties, $already_cloned));
          }
        }
      }
    }
    $cloned_entity->uid = \Drupal::currentUser()->id();
    $cloned_entity->save();
    $already_cloned[$entity->getEntityTypeId()][$entity->id()] = $cloned_entity;
    
    return $cloned_entity;
  }
  
  /**
   * Determines if a field is clonable.
   *
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The field definition.
   *
   * @return bool
   *   TRUE if th field is clonable; FALSE otherwise.
   */
  public static function fieldIsClonable(FieldDefinitionInterface $field_definition) {
    $clonable_field_types = [
      'entity_reference',
      'entity_reference_revisions',
      'panelizer'
    ];
    
    $type_is_clonable = in_array($field_definition->getType(), $clonable_field_types, TRUE);
    if (($field_definition instanceof FieldConfigInterface) && $type_is_clonable) {
      $settings = $field_definition->getSettings();
      if(!empty($settings['target_type']) && in_array($settings['target_type'], ['taxonomy_term','user'])) {
        return FALSE;
      }
      return TRUE;
    }
    return FALSE;
  }
  
  /**
   * Sets the cloned entity's label.
   *
   * @param \Drupal\Core\Entity\EntityInterface $original_entity
   *   The original entity.
   * @param \Drupal\Core\Entity\EntityInterface $cloned_entity
   *   The entity cloned from the original.
   */
  public static function setClonedEntityLabel(EntityInterface $original_entity, EntityInterface $cloned_entity) {
    $label_key = $original_entity->getEntityType()->getKey('label');
    if ($label_key && $cloned_entity->hasField($label_key)) {
      $cloned_entity->set($label_key, $original_entity->label() . ' - Cloned');
    }
  }
  
  /**
   * Clone referenced entities.
   *
   * @param \Drupal\Core\Field\FieldItemListInterface $field
   *   The field item.
   * @param \Drupal\Core\Field\FieldConfigInterface $field_definition
   *   The field definition.
   * @param array $properties
   *   All new properties to replace old.
   * @param array $already_cloned
   *   List of all already cloned entities, used for circular references.
   *
   * @return array
   *   field values.
   */
   public static function cloneField(FieldItemListInterface $field, FieldConfigInterface $field_definition, array $properties, array &$already_cloned) {
     $fieldType = $field_definition->getType();
     if($fieldType == 'panelizer') {
       return self::clonePanelizerField($field, $field_definition, $properties, $already_cloned);
     }else{
       return self::cloneReferencedEntities($field, $field_definition, $properties, $already_cloned);
     }
   }
  /**
   * Clone referenced entities.
   *
   * @param \Drupal\Core\Field\FieldItemListInterface $field
   *   The field item.
   * @param \Drupal\Core\Field\FieldConfigInterface $field_definition
   *   The field definition.
   * @param array $properties
   *   All new properties to replace old.
   * @param array $already_cloned
   *   List of all already cloned entities, used for circular references.
   *
   * @return array
   *   Referenced entities.
   */
   public static function cloneReferencedEntities(FieldItemListInterface $field, FieldConfigInterface $field_definition, array $properties, array &$already_cloned) {
    $referenced_entities = [];
    foreach ($field as $value) {
      // Check if we're not dealing with an entity
      // that has been deleted in the meantime.
      if (!$referenced_entity = $value->get('entity')->getTarget()) {
        continue;
      }
      /** @var \Drupal\Core\Entity\ContentEntityInterface $referenced_entity */
      $referenced_entity = $value->get('entity')->getTarget()->getValue();
      $cloned_reference = self::cloneEntity($referenced_entity, $properties, $already_cloned);
      $referenced_entities[] = $cloned_reference;
    }
    return $referenced_entities;
  }
  /**
   * Clone panelizer field.
   *
   * @param \Drupal\Core\Field\FieldItemListInterface $field
   *   The field item.
   * @param \Drupal\Core\Field\FieldConfigInterface $field_definition
   *   The field definition.
   * @param array $properties
   *   All new properties to replace old.
   * @param array $already_cloned
   *   List of all already cloned entities, used for circular references.
   *
   * @return array
   *   panelizer config vlues.
   */
  public static function clonePanelizerField(FieldItemListInterface $field, FieldConfigInterface $field_definition, array $properties, array &$already_cloned) {
    $items = [];
    
    $entity = $field->getEntity();
    /**
     * @var Panelizer $panelizer
     */
    $panelizer = \Drupal::service('panelizer');
    /**
     * @var PanelsDisplayManager $panels_manager
     */
    $panels_manager = \Drupal::service('panels.display_manager');
    
    foreach ($field as $index => $item) {
      $value = $item->getValue();
      $view_mode = $value['view_mode'];
      $panels_display = $panelizer->getPanelsDisplay($entity, $view_mode);
      
      $sample_display = $panels_manager->createDisplay();
      $sample_display->setLayout($panels_display->getLayout());
      $sample_display->setBuilder($panels_display->getBuilder());
      $sample_display->setPattern($panels_display->getPattern());
      $sample_display->setPageTitle($panels_display->getPageTitle());
      $sample_display->setWeight($panels_display->getWeight());
      
      $regions = $panels_display->getRegionAssignments();
      foreach ($regions as $region => $blocks) {
        if ($blocks) {
          foreach ($blocks as $block_id => $block){
            $config = $value['panels_display']['blocks'][$block_id];
            /**
             * @var BlockContentBlock $block
             */
            if ($block->getBaseId() == 'block_content') {
              $uuid = $block->getDerivativeId();
              $block_content = \Drupal::entityManager()->loadEntityByUuid('block_content', $uuid);
              if($block_content){
                $cloned_block = self::cloneEntity($block_content, $properties, $already_cloned);
                $config = $value['panels_display']['blocks'][$block_id];
                $config['id'] = "block_content:{$cloned_block->uuid()}";
                unset($config['uuid']);
              }
            }
            $sample_display->addBlock($config);
          }
        }
      }
      
      $values['view_mode'] = $view_mode;
      $values['default'] = $value['default'];
      $values['panels_display'] = $panels_manager->exportDisplay($sample_display);
      $items[] = $values;
      
    }
    return $items;
  }
}

