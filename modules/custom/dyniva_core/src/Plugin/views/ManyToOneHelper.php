<?php

namespace Drupal\dyniva_core\Plugin\views;

use Drupal\views\ManyToOneHelper as ManyToOneHelperBase;

/**
 * Custom many to one helper.
 */
class ManyToOneHelper extends ManyToOneHelperBase {

  /**
   * Add filter.
   *
   * {@inheritDoc}.
   *
   * @see \Drupal\views\ManyToOneHelper::addFilter()
   */
  public function addFilter() {
    if (empty($this->handler->value)) {
      return;
    }
    $this->handler->ensureMyTable();

    // Shorten some variables:
    $field = $this->getField();
    $options = $this->handler->options;
    $operator = $this->handler->operator;
    $formula = !empty($this->formula);
    $value = $this->handler->value;

    if ($options['with_depth']) {
      $parent = $value;
      foreach ($parent as $tid) {
        $tree = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadChildren($tid);
        foreach ($tree as $id => $term) {
          $value[] = $id . "";
        }
      }
    }
    if (empty($options['group'])) {
      $options['group'] = 0;
    }

    // add_condition determines whether a single expression is enough(FALSE) or
    // the conditions should be added via an db_or()/db_and() (TRUE).
    $add_condition = TRUE;
    if ($operator == 'not') {
      $value = NULL;
      $operator = 'IS NULL';
      $add_condition = FALSE;
    }
    elseif ($operator == 'or' && empty($options['reduce_duplicates'])) {
      if (is_array($value) && count($value) > 1) {
        $operator = 'IN';
      }
      else {
        $value = is_array($value) ? array_pop($value) : $value;
        $operator = '=';
      }
      $add_condition = FALSE;
    }

    if (!$add_condition) {
      if ($formula) {
        $placeholder = $this->placeholder();
        if ($operator == 'IN') {
          $operator = "$operator IN($placeholder)";
        }
        else {
          $operator = "$operator $placeholder";
        }
        $placeholders = [
          $placeholder => $value,
        ];
        $this->handler->query->addWhereExpression($options['group'], "$field $operator", $placeholders);
      }
      else {
        $placeholder = $this->placeholder();
        if (is_array($value) && count($value) > 1) {
          $placeholder .= '[]';

          if ($operator == 'IS NULL') {
            $this->handler->query->addWhereExpression(0, "$field $operator");
          }
          else {
            $this->handler->query->addWhereExpression(0, "$field $operator($placeholder)", [$placeholder => $value]);
          }
        }
        else {
          if ($operator == 'IS NULL') {
            $this->handler->query->addWhereExpression(0, "$field $operator");
          }
          else {
            $this->handler->query->addWhereExpression(0, "$field $operator $placeholder", [$placeholder => $value]);
          }
        }
      }
    }

    if ($add_condition) {
      $field = $this->handler->realField;
      $clause = $operator == 'or' ? db_or() : db_and();
      foreach ($this->handler->tableAliases as $value => $alias) {
        $clause->condition("$alias.$field", $value);
      }

      // Implode on either AND or OR.
      $this->handler->query->addWhere($options['group'], $clause);
    }
  }

}
