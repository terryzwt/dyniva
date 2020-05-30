<?php

namespace Drupal\dyniva_comment\Plugin\Field\FieldWidget;

use Drupal\Component\Utility\Html;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\comment\Plugin\Field\FieldWidget\CommentWidget as CommentWidgetBase;

class CommentWidget extends CommentWidgetBase {

  /**
   * Comments for this entity are hidden.
   */
  const HIDDEN = 0;
  
  /**
   * Comments for this entity are closed.
   */
  const CLOSED = 1;
  
  /**
   * Comments for this entity are open for authenticated.
   */
  const OPEN_AUTHENTICATED = 2;
  /**
   * Comments for this entity are open for anonymous.
   */
  const OPEN_ANONYMOUS = 3;
  
  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $entity = $items->getEntity();

    $element['status'] = [
      '#type' => 'radios',
      '#title' => t('Comments'),
      '#title_display' => 'invisible',
      '#default_value' => $items->status,
      '#options' => [
        self::CLOSED => t('Closed'),
//         self::HIDDEN => t('Open for author'),
//         self::OPEN_AUTHENTICATED => t('Open for authenticated'),
//         self::OPEN_ANONYMOUS => t('Open for anonymous'),
        self::OPEN_AUTHENTICATED => t('Open'),
      ],
      self::CLOSED => [
        '#description' => t('Users cannot post comments, and existing comments will not be displayed.'),
      ],
//       self::HIDDEN => [
//         '#description' => t('Comments are only show for content author or the comment author.'),
//       ],
//       self::OPEN_AUTHENTICATED => [
//         '#description' => t('Comments are show for authenticated user.'),
//       ],
//       self::OPEN_ANONYMOUS => [
//         '#description' => t('Comments are show for anonymous user.'),
//       ],
      self::OPEN_AUTHENTICATED => [
        '#description' => t('Comments are show for all.'),
      ],
    ];
    // If the advanced settings tabs-set is available (normally rendered in the
    // second column on wide-resolutions), place the field as a details element
    // in this tab-set.
    if (isset($form['advanced'])) {
      // Get default value from the field.
      $field_default_values = $this->fieldDefinition->getDefaultValue($entity);

      // Override widget title to be helpful for end users.
      $element['#title'] = $this->t('Comment settings');

      $element += [
        '#type' => 'details',
        // Open the details when the selected value is different to the stored
        // default values for the field.
        '#open' => ($items->status != $field_default_values[0]['status']),
        '#group' => 'advanced',
        '#attributes' => [
          'class' => ['comment-' . Html::getClass($entity->getEntityTypeId()) . '-settings-form'],
        ],
        '#attached' => [
          'library' => ['comment/drupal.comment'],
        ],
      ];
    }

    return $element;
  }

}
