<?php

namespace Drupal\dyniva_content_access\Plugin\Field\FieldWidget;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Field\Plugin\Field\FieldWidget\OptionsButtonsWidget as OptionsBase;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\dyniva_content_access\AccessFilter\UserFilter;
use Drupal\dyniva_content_access\AccessFilter\TaxonomyFilter;

/**
 * Plugin implementation of the 'dyniva_control_buttons' widget.
 *
 * @FieldWidget(
 * id = "dyniva_control_buttons",
 * label = @Translation("Access control buttons"),
 * field_types = {
 * "list_string",
 * },
 * multiple_values = TRUE
 * )
 */
class AccessControlWidget extends OptionsBase {

  /**
   * Form API callback: Processes a file_generic field element.
   *
   * Expands the file_generic type to include the description and display
   * fields.
   *
   * This method is assigned as a #process callback in formElement() method.
   */
  public static function process($element, FormStateInterface $form_state, $form) {
    $options = $element['#options'];
    foreach ($options as $key => $label) {
      $filter = \Drupal::service('dyniva_content_access.filter_manager')->getFilter($key);
      if($filter instanceof UserFilter) {
        self::processUserFilter($element, $form_state, $form, $filter, $key, $label);
      }elseif ($filter instanceof TaxonomyFilter) {
        $taxonomy_filters = $form_state->get('taxonomy_filters')?:[];
        $taxonomy_filters[] = $key;
        $form_state->set('taxonomy_filters', $taxonomy_filters);
        self::processTaxonomyFilter($element, $form_state, $form, $filter, $key, $label);
      }
    }
    return $element;
  }
  public static function processTaxonomyFilter(&$element, FormStateInterface $form_state, $form, TaxonomyFilter $filter, $key, $filter_label) {
    $entity = $form_state->getFormObject()->getEntity();
    
    $parents = $element['#parents'];
    $parents[] = $key;
    $elem = array_shift($parents);
    $elem .= '[' . implode('][', $parents) . ']';
    
    if(!isset($element['access_item_settings'])) {
      $element['access_item_settings'] = array(
        '#type' => 'container',
        '#tree' => true,
        '#weight' => 99
      );
    }
    
    $defaults = [];
    if ($form_state->has([
      'access_record',
      $key
    ])) {
      $defaults = $form_state->get([
        'access_record',
        $key
      ]);
    }
    else {
      if (isset($entity) && $entity->id()) {
        $conditions = [
          'entity_type' => $entity->getEntityTypeId(),
          'entity_id' => $entity->id(),
          'record_type' => $key
        ];
        $records = \Drupal::entityTypeManager()->getStorage('content_access_record')->loadByProperties($conditions);
        if (!empty($records)) {
//           $storage = \Drupal::entityTypeManager()->getStorage('taxonomy_term');
          foreach ($records as $record) {
//             $term = $storage->load($record->record_id->value);
//             if ($term) {
//               $defaults[$term->id()] = $term->label();
//             }
            $defaults[$record->record_id->value] = $record->record_id->value;
          }
        }
      }
    }
    
    $form_state->set([
      'access_record',
      $key
    ], $defaults);
    
    $wrapper = $element['#name'] . "{$key}-settings-ajax-wrapper";
    $element['access_item_settings'][$key] = array(
      '#type' => 'details',
      '#title' => $filter_label,
      '#open' => true,
      '#weight' => 1,
      '#prefix' => "<div id='{$wrapper}'>",
      '#suffix' => "</div>",
      '#attributes' => ['class' => ['access-item-settings-wrapper']],
      '#states' => array(
        'visible' => array(
          ':input[name="' . $elem . '"]' => array(
            'checked' => TRUE
          )
        )
      )
    );
    $vid = $filter->getVocabulary();
    $terms = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadTree($vid);
    $options = [];
    foreach ($terms as $item) {
      $options[$item->tid] = str_repeat('-', $item->depth) . $item->name;
    }
    $element['access_item_settings'][$key]['list'] = [
      '#type' => 'checkboxes',
      '#options' => $options,
      '#default_value' => $defaults,
    ];
  }
  public static function processUserFilter(&$element, FormStateInterface $form_state, $form, UserFilter $filter, $key, $filter_label) {
    $entity = $form_state->getFormObject()->getEntity();
    
    $parents = $element['#parents'];
    $parents[] = $key;
    $elem = array_shift($parents);
    $elem .= '[' . implode('][', $parents) . ']';
    
    if(!isset($element['access_item_settings'])) {
      $element['access_item_settings'] = array(
        '#type' => 'container',
        '#tree' => true,
        '#weight' => 99
      );
    }
    
    $defaults = [];
    if ($form_state->has([
      'access_record',
      $key
    ])) {
      $defaults = $form_state->get([
        'access_record',
        $key
      ]);
    }
    else {
      if (isset($entity) && $entity->id()) {
        $conditions = [
          'entity_type' => $entity->getEntityTypeId(),
          'entity_id' => $entity->id(),
          'record_type' => $key
        ];
        $records = \Drupal::entityTypeManager()->getStorage('content_access_record')->loadByProperties($conditions);
        if (!empty($records)) {
          foreach ($records as $record) {
            $user = user_load($record->record_id->value);
            if ($user) {
              $label = $user->getDisplayName();
              $defaults[$user->id()] = $label;
            }
          }
        }
      }
    }
    
    $form_state->set([
      'access_record',
      $key
    ], $defaults);
    
    $wrapper = $element['#name'] . "{$key}-settings-ajax-wrapper";
    $element['access_item_settings'][$key] = array(
      '#type' => 'details',
      '#title' => $filter_label,
      '#open' => true,
      '#weight' => 1,
      '#prefix' => "<div id='{$wrapper}'>",
      '#suffix' => "</div>",
      '#states' => array(
        'visible' => array(
          ':input[name="' . $elem . '"]' => array(
            'checked' => TRUE
          )
        )
      )
    );
    $module_path = drupal_get_path('module', 'dyniva_content_access');
    $url = Url::fromUserInput('/' . $module_path . '/tpl/access_users_tpl.xlsx', [
      'attributes' => [
        'target' => '_blank'
      ]
    ]);
    $tpl = Link::fromTextAndUrl(t('Download template.'), $url)->toString();
    $element['access_item_settings'][$key]['users'] = array(
      '#type' => 'file',
      '#description' => t('Upload user list File, only xls xlsx files allowed.'),
      '#prefix' => "<div>{$tpl}</div>",
      '#element_validate' => [
        [
          get_called_class(),
          'userListValidate'
        ]
      ],
      '#weight' => 0
    );
    $element['access_item_settings'][$key]['import'] = array(
      '#type' => 'button',
      '#value' => t('Upload'),
      '#name' => 'user-import',
      '#limit_validation_errors' => [],
      '#weight' => 1,
      '#ajax' => array(
        'callback' => [
          get_called_class(),
          'userListUpload'
        ],
        'wrapper' => $wrapper
      )
    );
    if (!empty($defaults)) {
      $element['access_item_settings'][$key]['list'] = array(
        '#type' => 'details',
        '#title' => t('ADDED USER'),
        '#open' => true,
        '#weight' => 2
      );
      foreach ($defaults as $uid => $fullName) {
        $element['access_item_settings'][$key]['list'][$uid] = array(
          '#type' => 'item'
        );
        
        $element['access_item_settings'][$key]['list'][$uid]['name'] = array(
          '#markup' => '<span>' . $fullName . '</span>'
        );
        $element['access_item_settings'][$key]['list'][$uid]['delete'] = array(
          '#type' => 'submit',
          '#value' => t('Delete'),
          '#name' => 'access-user-delete-' . $uid,
          '#limit_validation_errors' => [],
          '#submit' => [
            [
              get_called_class(),
              'userItemDeleteSubmit'
            ]
          ],
          '#executes_submit_callback' => TRUE,
          '#ajax' => array(
            'callback' => [
              get_called_class(),
              'userItemDeleteCallback'
            ],
            'wrapper' => $wrapper
          )
        );
      }
    }
  }

  /**
   *
   * @param array $form
   * @param FormStateInterface $form_state
   */
  public static function userListValidate(array $element, FormStateInterface $form_state) {
    $trigger = $form_state->getTriggeringElement();
    if (isset($trigger['#name']) && $trigger['#name'] == 'user-import') {
      $parents = $element['#parents'];
      $field = current(array_slice($parents, 0, 1));
      $validators = array(
        'file_validate_extensions' => [
          'xls xlsx'
        ]
      );
      if ($file = file_save_upload($field, $validators, FALSE, 0)) {
        
        try {
          $spreadsheet = IOFactory::load(drupal_realpath($file->getFileUri()));
          $sheetData = $spreadsheet->getActiveSheet()->toArray(null, true, true);
          $headers = array_shift($sheetData);
          
          $user_defaults = $form_state->get([
            'access_record',
            'user'
          ]);
          
          foreach ($sheetData as $line => $row) {
            $index = $line + 1;
            if ($headers) {
              $row = array_combine($headers, $row);
              if (empty($row['user_name'])) {
                drupal_set_message(t('<b><i>Row @index: </i></b>user_name is empty', [
                  '@index' => $index
                ]), 'error');
                continue;
              }
              $user = user_load_by_name(trim($row['user_name']));
              if ($user) {
                $label = $user->getDisplayName();
                \Drupal::moduleHandler()->alter('dyniva_content_access_user_label', $label, $user);
                $user_defaults[$user->id()] = $label;
              }
              else {
                drupal_set_message(t('<b><i>Row @index: </i></b>Not found user "@user"', [
                  '@index' => $index,
                  '@user' => $row['user_name']
                ]), 'error');
                continue;
              }
            }
          }
          $form_state->set([
            'access_record',
            'user'
          ], $user_defaults);
          $form_state->setRebuild();
        }
        catch (\Exception $e) {
          drupal_set_message(t("An error occured."), 'error');
        }
      }
    }
  }

  /**
   *
   * @param array $form
   * @param FormStateInterface $form_state
   */
  public static function userItemDeleteSubmit(array &$form, FormStateInterface $form_state) {
    $trigger = $form_state->getTriggeringElement();
    if (isset($trigger['#name']) && strpos($trigger['#name'], 'access-user-delete-') === 0) {
      $user_defaults = $form_state->get([
        'access_record',
        'user'
      ]);
      $entity = $form_state->getFormObject()->getEntity();
      
      $p = explode('-', $trigger['#name']);
      $uid = $p[3];
      if (isset($entity) && !$entity->isNew()) {
        $conditions = [
          'entity_type' => $entity->getEntityTypeId(),
          'entity_id' => $entity->id(),
          'record_type' => 'user',
          'record_id' => $uid
        ];
        $records = \Drupal::entityTypeManager()->getStorage('content_access_record')->loadByProperties($conditions);
        if (!empty($records)) {
          foreach ($records as $record) {
            $record->delete();
          }
        }
      }
      if (isset($user_defaults[$uid])) {
        unset($user_defaults[$uid]);
      }
      $form_state->set([
        'access_record',
        'user'
      ], $user_defaults);
    }
    $form_state->setRebuild();
  }

  /**
   *
   * @param array $form
   * @param FormStateInterface $form_state
   */
  public static function userItemDeleteCallback(array &$form, FormStateInterface $form_state) {
    $trigger = $form_state->getTriggeringElement();
    $parents = $trigger['#parents'];
    $parents = array_slice($parents, 0, -3);
    $element = NestedArray::getValue($form, $parents);
    return $element;
  }

  /**
   *
   * @param array $form
   * @param FormStateInterface $form_state
   */
  public static function userListUpload(array &$form, FormStateInterface $form_state) {
    $trigger = $form_state->getTriggeringElement();
    $parents = $trigger['#parents'];
    $parents = array_slice($parents, 0, -3);
    
    $element = NestedArray::getValue($form, $parents);
    if(empty($element) && isset($form['#group_children'])){
      $group = NestedArray::getValue($form['#group_children'], $parents);
      if(!empty($group)){
        $element = NestedArray::getValue($form[$group], $parents);
      }
    }
    
    return $element;
  }

  /**
   *
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element = parent::formElement($items, $delta, $element, $form, $form_state);
    
    $element_info = \Drupal::service('element_info')->getInfo('checkboxes');
    $element['#process'] = array_merge($element_info['#process'], [
      [
        get_class($this),
        'process'
      ]
    ]);
    $element['#value_callback'] = [
      get_called_class(),
      'valueCallback'
    ];
    return $element;
  }

  /**
   *
   * {@inheritdoc}
   */
  public function extractFormValues(FieldItemListInterface $items, array $form, FormStateInterface $form_state) {
    if (!$form_state->hasAnyErrors()) {
      $record_defaults = $form_state->get('access_record');
      if($taxonomy_filters = $form_state->get('taxonomy_filters')) {
        $field_name = $this->fieldDefinition->getName();
        
        // Extract the values from $form_state->getValues().
        $path = array_merge($form['#parents'], [$field_name]);
        foreach ($taxonomy_filters as $filter) {
          $filter_path = array_merge($path, ['access_item_settings',$filter,'list']);
          $values = NestedArray::getValue($form_state->getUserInput(), $filter_path);
          $values = array_filter($values);
          $record_defaults[$filter] = $values;
        }
      }
      $entity = $form_state->getFormObject()->getEntity();
      if (!empty($entity)) {
        $entity->content_access_settings = $record_defaults;
      }
    }
    parent::extractFormValues($items, $form, $form_state);
  }

  /**
   * Value callback.
   *
   * @param unknown $element
   * @param unknown $input
   * @param FormStateInterface $form_state
   * @return unknown[]|unknown|array
   */
  public static function valueCallback(&$element, $input, FormStateInterface $form_state) {
    if ($input === FALSE) {
      $value = [];
      $element += [
        '#default_value' => []
      ];
      foreach ($element['#default_value'] as $key) {
        $value[$key] = $key;
      }
      return $value;
    }
    elseif (is_array($input)) {
      // Programmatic form submissions use NULL to indicate that a checkbox
      // should be unchecked. We therefore remove all NULL elements from the
      // array before constructing the return value, to simulate the behavior
      // of web browsers (which do not send unchecked checkboxes to the server
      // at all). This will not affect non-programmatic form submissions, since
      // all values in \Drupal::request()->request are strings.
      // @see \Drupal\Core\Form\FormBuilderInterface::submitForm()
      foreach ($input as $key => $value) {
        if (!isset($value)) {
          unset($input[$key]);
        }
        if (is_array($value)) {
          unset($input[$key]);
        }
      }
      return array_combine($input, $input);
    }
    else {
      return [];
    }
  }

}
