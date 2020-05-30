<?php

namespace Drupal\dyniva_content_moderation\Plugin\views\field;

use Drupal\views\Plugin\views\field\EntityField;

/**
 * Field handler to present a link to a node revisions.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("content_moderation_state_label")
 */
class ModerateLabel extends EntityField {

  /**
   * {@inheritdoc}
   */
  protected function prepareItemsByDelta(array $all_values) {
    $values = parent::prepareItemsByDelta($all_values);
    foreach ($values as $key => $value){
      $values[$key] = t(ucwords(str_replace('_', ' ', $value)));
    }
    return $values;
  }

}
