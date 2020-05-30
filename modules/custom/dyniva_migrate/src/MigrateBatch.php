<?php

namespace Drupal\dyniva_migrate;

use Drupal\migrate_tools\MigrateExecutable;
use Drupal\Core\Render\Markup;
use Drupal\migrate\Plugin\MigrationInterface;

/**
 * Migrate Batch.
 */
class MigrateBatch {

  /**
   * Process callback.
   */
  public static function processCallback($data, $migration_id, $migration_config, &$context) {

    $manager = \Drupal::service('plugin.manager.migration');
    /*
     * @var \Drupal\migrate\Plugin\Migration $migration
     */
    $migration = $manager->createInstance($migration_id);

    $session_row = new MigrateSessionRow($migration);
    $session_row->setCurrent($data);

    $log = new BatchMigrateMessage();
    $executable = new MigrateExecutable($migration, $log);
    $return = $executable->import();

    $msgs = $log->getMessage();
    if (!empty($msgs)) {
      $message = implode(' ', $msgs);
      $context['results']['message'][] = $message;
    }

    if ($return == MigrationInterface::RESULT_FAILED) {
      $context['finished'] = 1;
    }

    if (empty($context['results'])) {
      $context['results']['message'] = [];
      $context['results']['counter'] = [
        '@numitems' => 0,
        '@created' => 0,
        '@updated' => 0,
        '@failures' => 0,
        '@ignored' => 0,
        '@name' => $migration->label(),
        '@id' => $migration->id(),
      ];
    }

    $context['results']['counter'] = [
      '@numitems' => $context['results']['counter']['@numitems'] + $executable->getProcessedCount(),
      '@created' => $context['results']['counter']['@created'] + $executable->getCreatedCount(),
      '@updated' => $context['results']['counter']['@updated'] + $executable->getUpdatedCount(),
      '@failures' => $context['results']['counter']['@failures'] + $executable->getFailedCount(),
      '@ignored' => $context['results']['counter']['@ignored'] + $executable->getIgnoredCount(),
      '@name' => $migration->label(),
      '@id' => $migration->id(),
    ];
  }

  /**
   * Finish callback.
   */
  public static function finishedCallback($success, $results, $operations) {
    // The 'success' parameter means no fatal PHP errors were detected. All
    // other error management should be handled using 'results'.
    if ($success) {
      $singular_message = "Processed 1 item (@created created, @updated updated, @failures failed, @ignored ignored) - done with '@name'";
      $plural_message = "Processed @numitems items (@created created, @updated updated, @failures failed, @ignored ignored) - done with '@name'";
      drupal_set_message(\Drupal::translation()->formatPlural($results['counter']['@numitems'],
          $singular_message,
          $plural_message,
          $results['counter']));
    }
    else {
      $message = t('Finished with an error.');
      drupal_set_message($message);
    }

    $process_msg = implode('<br/>', $results['message']);
    drupal_set_message($process_msg);

    $migration_id = $results['counter']['@id'];
    $manager = \Drupal::service('plugin.manager.migration');
    /*
     * @var \Drupal\migrate\Plugin\Migration $migration
     */
    $migration = $manager->createInstance($migration_id);
    $map = $migration->getIdMap();
    $first = TRUE;
    $header = [];
    $table = [];
    foreach ($map->getMessageIterator() as $row) {
      unset($row->msgid);
      if ($first) {
        foreach ($row as $column => $value) {
          $header[] = $column;
        }
        $first = FALSE;
      }
      $table[] = (array) $row;
    }
    $map->clearMessages();
    if (!empty($table)) {
      $message_table = [
        '#theme' => 'table',
        '#header' => $header,
        '#rows' => $table,
      ];
      $table_str = \Drupal::service('renderer')->renderRoot($message_table);
      $table_str = Markup::create($table_str);
      drupal_set_message($table_str);
    }
  }

}
