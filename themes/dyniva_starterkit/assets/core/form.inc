<?php
/**
 * Implements HOOK_theme().
 * Functions to support  Form hook in the dyniva_starterkit theme.
 */

use Drupal\Core\Template\Attribute;
use Drupal\Core\Form\FormStateInterface;

/**
 * Implements hook_form_alter() to add classes to the search form.
 */
  function dyniva_starterkit_preprocess_form(array &$variables) {
   $element = $variables['element'];
  }

  function dyniva_starterkit_preprocess_input(array &$variables) {
    $element = $variables['element'];
    if(($element['#type'] == 'textfield' || $element['#type'] == 'email') && !empty($element['#title'])){
      $variables['attributes']['placeholder'] = $element['#title'];
    }
  }

  function dyniva_starterkit_preprocess_textarea(&$variables) {
    $element = $variables['element'];
    if(!empty($element['#title'])){
      $variables['attributes']['placeholder'] = $element['#title'];
    }
  }

  // function dyniva_starterkit_preprocess_select(array &$variables) {
  //   //$variables['attributes']['class'][] = 'select-chosen';
  // }

/**
 * Implements hook_form_alter(&$form, FormStateInterface $form_state, $form_id).
 */

function dyniva_starterkit_form_alter(&$form, FormStateInterface $form_state, $form_id) {
  $key = 'actions';
  if (isset($form['basic']) && !isset($form['basic'][$key])) {
    $form['basic'][$key]['#type'] = 'actions';
    $form['basic'][$key]['submit'] = $form['basic']['submit'];
    unset($form['basic']['submit']);
  }

  // if($form_id == 'search_block_form'){
  // }

  if(($form_id == 'search_form') && isset($form['help_link'])){
    if(isset($form['basic']) && isset($form['basic'][$key])){
      $form['basic'][$key][] = $form['help_link'];
      unset($form['help_link']);
    }
  }
}

/**
 * Implements hook_preprocess_form_element(&$variables).
 */
function dyniva_starterkit_preprocess_form_element(&$variables) {
  $element = $variables['element'];

  // Get the title
  if (isset($element['#title']) && $element['#title'] !== '') {
    $variables['title'] = ['#markup' => $element['#title']];
  }

  // Modify Machine name element
  if (!empty($element['#type']) && $element['#type'] == 'machine_name'){
  }

  // Modify the label variable
  if (!empty($element['#type']) && in_array($element['#type'], array('checkbox', 'radio'))) {
    // Set the label variable to just the title for checkbox and radio inputs
    $variables['label'] = $variables['title'];
  } else {
    // Use default label rendering for any other inputs
    $variables['label'] = array('#theme' => 'form_element_label');
    $variables['label'] += array_intersect_key($element, array_flip(array('#id', '#required', '#title', '#title_display')));
  }
}


/*
* Implements hook_theme_suggestions_HOOK_alter(array &$suggestions, array $variables, $hook)
*/
/*
  function dyniva_starterkit_theme_suggestions_input_alter(array &$suggestions, array $variables, $hook) {
      $element = $variables['element'];
     if(!empty($element['#name']) && $element['#name'] == 'textfield-machine'){
      kint($variables);
      kint($suggestions);
      $suggestions[] = 'input__textfield_machine';
     };
  }
//*/


/**
 * Implements hook_form_BASE_FORM_ID_alter() for \Drupal\node\NodeForm.
 *
 * Changes vertical tabs to container and adds meta information.
*/
function dyniva_starterkit_form_node_form_alter(&$form, FormStateInterface $form_state) {
  /** @var \Drupal\node\NodeInterface $node */
  $node = $form_state->getFormObject()->getEntity();

  $form['#theme'] = ['node_edit_form'];

  $form['advanced']['#type'] = 'container';
  $is_new = !$node->isNew() ? format_date($node->getChangedTime(), 'short') : t('Not saved yet');
  $form['meta'] = [
    '#attributes' => ['class' => ['entity-meta__header']],
    '#type' => 'details',
    '#open' => TRUE,
    '#title' => $node->isPublished() ? t('Publish') : t('Not published'),
    //'#group' => 'advanced',
    '#weight' => -100,
    'changed' => [
      '#type' => 'item',
      '#wrapper_attributes' => ['class' => ['entity-meta__last-saved', 'container-inline']],
      '#markup' => '<h4 class="label inline">' . t('Last saved') . '</h4> ' . $is_new,
    ],
    'author' => [
      '#type' => 'item',
      '#wrapper_attributes' => ['class' => ['author', 'container-inline']],
      '#markup' => '<h4 class="label inline">' . t('Author') . '</h4> ' . $node->getOwner()->getUsername(),
    ],
  ];
  $form['revision_information']['#type'] = 'container';
  $form['revision_information']['#group'] = 'meta';
}
