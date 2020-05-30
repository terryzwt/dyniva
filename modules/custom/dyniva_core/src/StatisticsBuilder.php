<?php

namespace Drupal\dyniva_core;

use Drupal\statistics\StatisticsViewsResult;

/**
 * TODO Doc comment is empty.
 */
class StatisticsBuilder {

  /**
   * Missing function doc comment.
   */
  public function buildCount($entity_type_id, $entity_id) {

    $count = 0;
    $storage_id = "statistics.storage.{$entity_type_id}";

    if (\Drupal::hasService($storage_id)) {
      $statistics = \Drupal::service($storage_id)->fetchView($entity_id);
      if ($statistics instanceof StatisticsViewsResult) {
        $count = $statistics->getTotalCount();
      }
    }
    return [
      '#markup' => "<span>{$count}</span>",
    ];
  }

}
