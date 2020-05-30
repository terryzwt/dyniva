<?php

namespace Drupal\dyniva_editor_panelizer\Plugin\Field\FieldWidget;

use Drupal\Core\Entity\Entity\EntityViewDisplay;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\panelizer\Plugin\Field\FieldWidget\PanelizerWidget;
use Drupal\Component\Utility\SafeMarkup;
use Drupal\image\Entity\ImageStyle;

/**
 * Plugin implementation of the 'panelizer' widget.
 *
 * @FieldWidget(
 *   id = "dyniva_panelizer",
 *   label = @Translation("Dyniva Panelizer"),
 *   multiple_values = TRUE,
 *   field_types = {
 *     "panelizer"
 *   }
 * )
 */
class CcmsPanelizerWidget extends PanelizerWidget {
  
  
  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $entity = $items->getEntity();
    $entity_type_id = $entity->getEntityTypeId();
    $entity_view_modes = $this->getEntityDisplayRepository()->getViewModes($entity_type_id);
    
    // Get the current values from the entity.
    $values = [];
    /** @var \Drupal\Core\Field\FieldItemInterface $item */
    foreach ($items as $item) {
      $values[$item->view_mode] = [
        'default' => $item->default,
        'panels_display' => $item->panels_display,
      ];
    }
    
    // If any view modes are missing, then set the default.
    $displays = [];
    if(empty($entity_view_modes)){
      $entity_view_modes = ['full' => [
        'label' => t('Page layout')
      ]];
    }
    foreach ($entity_view_modes as $view_mode => $view_mode_info) {
      $display = EntityViewDisplay::collectRenderDisplay($entity, $view_mode);
      $displays[$view_mode] = $display->getThirdPartySetting('panelizer', 'displays', []);
      // If we don't have a value, or the default is __bundle_default__ and our
      // panels_display is empty, set the default to __bundle_default__.
      if (!isset($values[$view_mode]) || ($values[$view_mode]['default'] == '__bundle_default__' && empty($values[$view_mode]['panels_display']))) {
        if ($display->getThirdPartySetting('panelizer', 'enable', FALSE)) {
          $values[$view_mode] = [
            'default' => '__bundle_default__',
            'panels_display' => [],
          ];
        }
      }
    }
    
    // Add elements to the form for each view mode.
    $delta = 0;
    foreach ($values as $view_mode => $value) {
      $element[$delta]['view_mode'] = [
        '#type' => 'value',
        '#value' => $view_mode,
      ];
      
      $settings = $this->getPanelizer()->getPanelizerSettings($entity_type_id, $entity->bundle(), $view_mode);
      if (!empty($settings['allow'])) {
        // We default to this option when the user hasn't previous interacted
        // with the field.
        $options = [
        //           '__bundle_default__' => $this->t('Current default display'),
        ];
        if($value['default'] == '__bundle_default__'){
          $value['default'] =  $settings['default'];
        }
        foreach ($displays[$view_mode] as $machine_name => $panels_display) {
          $options[$machine_name] = $panels_display['label'];
        }
        $storage = \Drupal::entityTypeManager()->getStorage('panelizer_display_attribute');
        foreach ($options as $key => $label){
          $skey = $key;
          if($skey == '__bundle_default__'){
            $skey =  $settings['default'];
          }
          $variant_id = $entity_type_id . '__' . $entity->bundle() .'__' . $view_mode .'__'. $skey;
          $attrs = $storage->loadByProperties(['display_variant_id' => $variant_id]);
          if(empty($attrs)){
            $variant_id = $entity_type_id . '__' . $entity->bundle() .'__default__'. $skey;
            $attrs = $storage->loadByProperties(['display_variant_id' => $variant_id]);
          }
          
          if(!empty($attrs)){
            $attr = reset($attrs);
            $image_uri = $attr->image->entity->getFileUri();
            $full = file_create_url($image_uri);
            $thumbnail = ImageStyle::load('thumbnail')->buildUrl($image_uri);
            $options[$key] = SafeMarkup::format(t($label) . '<br/><a href="@full" title="@title" data-colorbox-gallery="" class="colorbox" data-cbox-img-attrs="{&quot;title&quot;:&quot;@title&quot;,&quot;alt&quot;:&quot;缩略图&quot;}">
            <img src="@thumbnail" typeof="foaf:Image" class="ccms-radio-layout-cover"></a>',
                [
                  '@full' => $full,
                  '@thumbnail' => $thumbnail,
                  '@title' => $attr->description->value,
                ]);
          }
          
        }
        $element[$delta]['default'] = [
          '#title' => $entity_view_modes[$view_mode]['label'],
          '#type' => 'radios',
          '#options' => $options,
          '#default_value' => $value['default'],
        ];
        // If we have a value in panels_display, prevent the user from
        // interacting with the widget for the view modes that are overridden.
        if (!empty($value['panels_display'])) {
          $element[$delta]['default']['#disabled'] = TRUE;
          $element[$delta]['default']['#options'][$value['default']] = $this->t('Custom Override');
        }
      }
      else {
        $element[$delta]['default'] = [
          '#type' => 'value',
          '#value' => $value['default'],
        ];
      }
      
      $element[$delta]['panels_display'] = [
        '#type' => 'value',
        '#value' => $value['panels_display'],
      ];
      
      $delta++;
    }
    
    $element['#attached']['library'][] = 'colorbox/colorbox';
    $element['#attached']['library'][] = 'colorbox/init';
    $element['#attached']['library'][] = 'colorbox/default';
    $element['#attached']['drupalSettings']['colorbox'] = [];
    
    return $element;
  }
  
}
