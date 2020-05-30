<?php

namespace Drupal\dyniva_media\Plugin\Field\FieldWidget;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\file\Plugin\Field\FieldWidget\FileWidget;
use Drupal\file\Entity\File;

/**
 * @FieldWidget(
 *   id = "file_dyniva",
 *   label = @Translation("Dyniva File 已废弃"),
 *   field_types = {
 *     "file"
 *   }
 * )
 */
class FileMediaWidget extends FileWidget implements ContainerFactoryPluginInterface {

  /**
   * {@inheritdoc}
   */
  public static function process($element, FormStateInterface $form_state, $form) {
    
    // Add custom uri
    if (!empty($element['#files']) && empty($element['custom_uri'])) {
      $file = reset($element['#files']);
      $uri = $file->getFileUri();
      $schema = file_uri_scheme($uri) . '://';
      $element['custom_uri'] = [
        '#type' => 'textfield',
        '#title' => t('Uri'),
        '#default_value' => str_replace($schema,'',$uri),
        '#description' => t('The file uri.'),
        '#maxlength' => 255,
        '#required' => TRUE,
        '#element_validate' => ['dyniva_media_validateFileUri'],
        '#field_name' => $element['#field_name'],
        '#weight' => 0
      ];
      $element['original_uri'] = [
        '#type' => 'hidden',
        '#value' => $uri,
      ];
    }

    return parent::process($element, $form_state, $form);
  }
}
