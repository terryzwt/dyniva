<?php

namespace Drupal\dyniva_editor_panelizer;

use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Entity\RevisionableInterface;
use Drupal\panelizer\Exception\PanelizerException;
use Drupal\panels\Plugin\DisplayVariant\PanelsDisplayVariant;
use Drupal\panelizer\Panelizer as PanelizerBase;
use Drupal\Core\Entity\Display\EntityViewDisplayInterface;

/**
 * The Panelizer service.
 */
class Panelizer extends PanelizerBase {

  /**
   * {@inheritdoc}
   */
  public function getPanelsDisplay(FieldableEntityInterface $entity, $view_mode, EntityViewDisplayInterface $display = NULL) {
    $settings = $this->getPanelizerSettings($entity->getEntityTypeId(), $entity->bundle(), $view_mode, $display);
    if (($settings['custom'] || $settings['allow']) && isset($entity->panelizer) && $entity->panelizer->first()) {
      /** @var \Drupal\Core\Field\FieldItemInterface[] $values */
      $values = [];
      foreach ($entity->panelizer as $item) {
        $values[$item->view_mode] = $item;
      }
      if (isset($values[$view_mode])) {
        $panelizer_item = $values[$view_mode];
        // Check for a customized display first and use that if present.
        if (!empty($panelizer_item->panels_display)) {
          // @todo: validate schema after https://www.drupal.org/node/2392057 is fixed.
          $config = $panelizer_item->panels_display;
          $config['uuid'] = $entity->uuid();
          return $this->panelsManager->importDisplay($config, FALSE);
        }
        // If not customized, use the specified default.
        if (!empty($panelizer_item->default)) {
          // If we're using this magic key use the settings default.
          if ($panelizer_item->default == '__bundle_default__') {
            $default = $settings['default'];
          }
          else {
            $default = $panelizer_item->default;
            // Ensure the default still exists and if not fallback sanely.
            $displays = $this->getDefaultPanelsDisplays($entity->getEntityTypeId(), $entity->bundle(), $view_mode);
            if (!isset($displays[$default])) {
              $default = $settings['default'];
            }
          }
          $panels_display = $this->getDefaultPanelsDisplay($default, $entity->getEntityTypeId(), $entity->bundle(), $view_mode, $display);
          $this->setCacheTags($panels_display, $entity->getEntityTypeId(), $entity->bundle(), $view_mode, $display, $default, $settings);
          return $panels_display;
        }
      }
    }
    // If the field has no input to give us, use the settings default.
    $panels_display = $this->getDefaultPanelsDisplay($settings['default'], $entity->getEntityTypeId(), $entity->bundle(), $view_mode, $display);
    $this->setCacheTags($panels_display, $entity->getEntityTypeId(), $entity->bundle(), $view_mode, $display, $settings['default'], $settings);
    return $panels_display;
  }

  /**
   * {@inheritdoc}
   */
  public function setPanelsDisplay(FieldableEntityInterface $entity, $view_mode, $default, PanelsDisplayVariant $panels_display = NULL, $log = '') {
    $settings = $this->getPanelizerSettings($entity->getEntityTypeId(), $entity->bundle(), $view_mode);
    if (($settings['custom'] || $settings['allow']) && isset($entity->panelizer)) {
      $panelizer_item = NULL;
      /** @var \Drupal\Core\Field\FieldItemInterface $item */
      foreach ($entity->panelizer as $item) {
        if ($item->view_mode == $view_mode) {
          $panelizer_item = $item;
          break;
        }
      }
      if (!$panelizer_item) {
        $panelizer_item = $this->fieldTypeManager->createFieldItem($entity->panelizer, count($entity->panelizer));
        $panelizer_item->view_mode = $view_mode;
      }

      // Note: We don't call $panels_display->setStorage() here because it will
      // be set internally by PanelizerFieldType::postSave() which will know
      // the real revision ID of the newly saved entity.
      $panelizer_item->panels_display = $panels_display ? $this->panelsManager->exportDisplay($panels_display) : [];
      $panelizer_item->default = $default;

      // Create a new revision if possible.
      if ($entity instanceof RevisionableInterface && $entity->getEntityType()->isRevisionable()) {
        if ($entity->isDefaultRevision()) {
          $entity->setNewRevision(TRUE);
          $entity->setRevisionCreationTime(REQUEST_TIME);
          $entity->setRevisionLogMessage($log);
        }
      }

      // Updates the changed time of the entity, if necessary.
      if ($entity->getEntityType()->isSubclassOf(EntityChangedInterface::class)) {
        $entity->setChangedTime(REQUEST_TIME);
      }

      $entity->panelizer[$panelizer_item->getName()] = $panelizer_item;

      $entity->save();
    }
    else {
      throw new PanelizerException("Custom overrides not enabled on this entity, bundle and view mode");
    }
  }

}
