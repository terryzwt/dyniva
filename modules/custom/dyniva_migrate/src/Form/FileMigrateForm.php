<?php

namespace Drupal\dyniva_migrate\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\file\Entity\File;
use Drupal\migrate\MigrateException;
use Drupal\migrate_source_csv\CSVFileObject;
use Drupal\dyniva_migrate\MigrateSessionRow;
use Drupal\migrate\Plugin\MigrationInterface;
use PhpOffice\PhpSpreadsheet\IOFactory;
use League\Csv\Reader;

/**
 * Migrate form with upload csv file.
 */
class FileMigrateForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'dyniva_migrate_file_migrate_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    /*
     * @var \Drupal\migrate\Plugin\MigrationPluginManager $manager
     */
    $manager = \Drupal::service('plugin.manager.migration');
    $migrations = $manager->createInstances([]);

    $options = [];
    /*
     * @var \Drupal\migrate\Plugin\Migration $migration
     */
    foreach ($migrations as $id => $migration) {
      if ($migration->getSourcePlugin()->getPluginId() == 'batch') {
        $options[$migration->id()] = $migration->label();
      }
    }
    asort($options);
    $form['migration'] = [
      '#type' => 'select',
      '#title' => $this->t('Migration'),
      '#options' => $options,
      '#description' => t('Only the migration with batch source available.'),
      '#required' => TRUE,
      '#ajax' => [
        'wrapper' => 'migration-config-ajax-wrapper',
        'callback' => '::migrationAjaxCallback',
      ],
    ];
    $uppath = 'public://migrate/';
    $validators = [
      'file_validate_extensions' => ['csv xls xlsx'],
    ];
    $form['file'] = [
      '#type' => 'managed_file',
      '#title' => $this->t('Import File'),
      '#field_name' => 'file',
      '#upload_location' => $uppath,
      '#upload_validators' => $validators,
      '#description' => t('Please select a xls, xlsx or csv file with UTF-8 None BOM encode.'),
      '#required' => TRUE,
    ];
    $form['migration_config'] = [
      '#type' => 'details',
      '#title' => 'Migration Config',
      '#open' => TRUE,
      '#tree' => TRUE,
      '#prefix' => '<div id="migration-config-ajax-wrapper">',
      '#suffix' => '</div>',
    ];
    if ($form_state->getTriggeringElement() && !empty($form_state->getValues()['migration'])) {
      $plugin_id = $form_state->getValues()['migration'];
      $migration = $manager->createInstance($plugin_id);
      $source_config = $migration->getSourceConfiguration();
      $dependencies = $migration->getMigrationDependencies();
      $form['migration_config']['dependencies'] = [
        '#type' => 'details',
        '#title' => 'Dependencies',
        '#open' => TRUE,
        '#tree' => TRUE,
      ];
      if (!empty($dependencies['optional'])) {
        $form['migration_config']['dependencies']['optional'] = [
          '#theme' => 'item_list',
          '#title' => 'Optional',
          '#items' => $dependencies['optional'],
        ];
      }
      if (!empty($dependencies['required'])) {
        $form['migration_config']['dependencies']['required'] = [
          '#theme' => 'item_list',
          '#title' => 'Required',
          '#items' => $dependencies['required'],
        ];
      }
    }
    $form['update'] = [
      '#type' => 'checkbox',
      '#title' => t('Update exist entity'),
      '#default_value' => 1,
    ];
    $form['reset'] = [
      '#type' => 'checkbox',
      '#title' => t('Reset migration status'),
      '#default_value' => 0,
    ];
    $form['destroy'] = [
      '#type' => 'checkbox',
      '#title' => t('Destroy previous migration map'),
      '#default_value' => 0,
    ];

    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['submit'] = [
      '#value' => $this->t('Submit'),
      '#type' => 'submit',
      '#button_type' => 'primary',
    ];

    return $form;
  }

  /**
   * Ajax callback.
   *
   * @param array $form
   *   Form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state object.
   *
   * @return array
   *   Render array.
   */
  public function migrationAjaxCallback(array &$form, FormStateInterface $form_state) {
    return $form['migration_config'];
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {

  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $file = $form_state->getValue('file');
    $migration_id = $form_state->getValue('migration');
    $update = $form_state->getValue('update');
    $reset = $form_state->getValue('reset');
    $destroy = $form_state->getValue('destroy');
    $migration_config = $form_state->getValue('migration_config');
    if (!empty($file)) {
      $file = File::load($file['0']);
      $file->setPermanent();
      $file->save();

      $manager = \Drupal::service('plugin.manager.migration');
      /*
       * @var \Drupal\migrate\Plugin\Migration $migration
       */
      $migration = $manager->createInstance($migration_id);

      $id_map = $migration->getIdMap();

      if ($update) {
        $id_map->prepareUpdate();
      }
      if ($destroy) {
        $id_map->destroy();
        $id_map->getDatabase();
      }

      $status = $migration->getStatus();
      if ($reset) {
        if ($status != MigrationInterface::STATUS_IDLE) {
          $migration->setStatus(MigrationInterface::STATUS_IDLE);
        }
      }
      else {
        if ($status != MigrationInterface::STATUS_IDLE) {
          drupal_set_message(t('Migration @id status is not Idle.', ['@id' => $migration->label()]), 'notice');
          return;
        }
      }
      $conf = $migration->getSourceConfiguration();

      $uri = $file->getFileUri();

      if(preg_match('/\.csv$/', $uri)) {
        $reader = $this->getReader($conf, $uri);
        $header = $reader->getHeader();
        $records = $reader->getRecords($header);
        $op = [];
        foreach ($records as $record) {
          $op[] = [
            '\Drupal\dyniva_migrate\MigrateBatch::processCallback',
            [
              $record,
              $migration_id,
              $migration_config,
            ],
          ];
        }
      } else {
        // Load the workbook, xls, xlsx file
        try {
          $file_path = \Drupal::service('file_system')->realpath($uri);

          // Identify the type of the input file.
          $type = IOFactory::identify($file_path);

          // Create a new Reader of the file type.
          /** @var \PhpOffice\PhpSpreadsheet\Reader\BaseReader $reader */
          $reader = IOFactory::createReader($type);

          // Advise the Reader that we only want to load cell data.
          $reader->setReadDataOnly(TRUE);

          /** @var \PhpOffice\PhpSpreadsheet\Spreadsheet $workbook */
          $workbook = $reader->load($file_path);

          $sheetData = $workbook->getActiveSheet()->toArray(null, true, true, true);
          $headers = [];

          if(count($sheetData))foreach($sheetData as $row) {
            if(!$headers) {
              $headers = array_filter($row);
            } else {
              $mergedRow = [];
              foreach($headers as $key => $col) {
                $mergedRow[$col] = $row[$key];
              }
              $filter = array_filter($mergedRow, function($col) {
                return isset($col);
              });
              if($filter) {
                $op[] = [
                  '\Drupal\dyniva_migrate\MigrateBatch::processCallback',
                  [
                    $mergedRow,
                    $migration_id,
                    $migration_config,
                  ],
                ];
              }
            }
          }
        }
        catch (\Exception $e) {
          $class = get_class($e);
          throw new MigrateException("Got '$class', message '{$e->getMessage()}'.");
        }
      }

      if (!empty($op)) {
        $total = count($op);
        $session_row = new MigrateSessionRow($migration);
        $session_row->setCount($total);

        $batch = [
          'title' => t('Migrating %migrate', ['%migrate' => $migration->label()]),
          'init_message' => t('Start migrating %migrate', ['%migrate' => $migration->label()]),
          'error_message' => t('An error occurred while migrating %migrate.', ['%migrate' => $migration->label()]),
          'operations' => $op,
          'finished' => '\Drupal\dyniva_migrate\MigrateBatch::finishedCallback',
        ];
        batch_set($batch);
      }
      else {
        drupal_set_message(t('No valid data to process.'), 'notice');
      }
    }
  }
  /**
   * Get the CSV reader.
   *
   * @return \League\Csv\Reader
   *   The reader.
   *
   * @throws \Drupal\migrate\MigrateException
   * @throws \League\Csv\Exception
   */
  protected function getReader($conf, $path) {
    $reader = $this->createReader($path);
    $delimiter = !empty($conf['delimiter']) ? $conf['delimiter'] : ',';
    $enclosure = !empty($conf['enclosure']) ? $conf['enclosure'] : '"';
    $escape = !empty($conf['escape']) ? $conf['escape'] : '\\';
    $header_offset = !empty($conf['header_offset']) ? $conf['header_offset'] : 0;

    $reader->setDelimiter($delimiter);
    $reader->setEnclosure($enclosure);
    $reader->setEscape($escape);
    $reader->setHeaderOffset($header_offset);
    return $reader;
  }

  /**
   * Construct a new CSV reader.
   *
   * @return \League\Csv\Reader
   *   The reader.
   */
  protected function createReader($path) {
    return Reader::createFromStream(fopen($path, 'r'));
  }
}
