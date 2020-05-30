<?php

namespace Drupal\dyniva_core\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\file\Entity\File;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Drupal\Component\Utility\SafeMarkup;

/**
 * Class BaseImportForm.
 *
 * @package Drupal\dyniva_core\Form
 */
class BaseImportForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'base_import_form';
  }

  /**
   * Get page title.
   */
  public function getTitle() {
    return $this->t('Import File');
  }

  /**
   * Get import template file url.
   *
   * @return \Drupal\Core\Url|null
   *   Template.
   */
  public function getTemplate() {
    return FALSE;
  }

  /**
   * Get import field map array.
   */
  public function getFieldMap() {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $storage = &$form_state->getStorage();
    if (!isset($storage['file'])) {
      $storage['step'] = 1;
      $uppath = 'public://import/';
      $validators = [
        'file_validate_extensions' => ['csv xls xlsx'],
        'file_validate_size' => [20 * 1024 * 1024],
      ];
      $form['file'] = [
        '#type' => 'managed_file',
        '#title' => $this->t('Import File'),
        '#field_name' => 'file',
        '#upload_location' => $uppath,
        '#upload_validators' => $validators,
        '#description' => t('File max size 20M,suffix with csv xls or xlsx.'),
        '#required' => TRUE,
      ];

      if ($tpl_url = $this->getTemplate()) {
        $tpl = \Drupal::l($this->t('Download import template'), $tpl_url);

        $form['template'] = [
          '#type' => 'item',
          '#markup' => '<b>' . $tpl . '</b>',
        ];
      }
      if ($map = $this->getFieldMap()) {
        foreach ($map as $field => $config) {
          $header[] = $config['label'];
          $row[] = isset($config['example']) ? $config['example'] : '';
          $required = $config['required'] ? $this->t('Required') : $this->t('Optional');
          $desc = !empty($config['desc']) ? '，' . $config['desc'] : '';
          $detail[] = $config['label'] . '：' . $required . $desc;
        }
        $form['example'] = [
          '#theme' => 'table',
          '#header' => $header,
          '#rows' => [
            $row,
          ],
          '#caption' => $this->t('Template notes'),
        ];
        $form['detail'] = [
          '#type' => 'item',
          '#theme' => 'item_list',
          '#type' => 'ul',
          '#items' => $detail,
        ];
      }
      $form['actions'] = [
        '#type' => 'actions',
        '#weight' => 99,
      ];
      $form['actions']['submit'] = [
        '#type' => 'submit',
        '#value' => $this->t('Upload And Statistics'),
        '#attributes' => ['class' => ['btn', 'btn-primary']],
      ];
    }
    else {
      $storage['step'] = 2;
      $file = $storage['file'];
      $confirm = $this->confirmForm($file);
      $form['desc'] = [
        '#type' => 'item',
        '#markup' => $confirm,
      ];
      $form['actions'] = [
        '#type' => 'actions',
        '#weight' => 99,
      ];
      $form['actions']['submit'] = [
        '#type' => 'submit',
        '#value' => $this->t('Confirm Import'),
        '#attributes' => ['class' => ['btn', 'btn-primary']],
      ];
    }
    return $form;
  }

  /**
   * Get import confirm infomation.
   *
   * @param \Drupal\file\Entity\File $file
   *   File.
   */
  public function confirmForm(File $file) {
    $spreadsheet = IOFactory::load(drupal_realpath($file->getFileUri()));
    $total = $spreadsheet->getActiveSheet()->getHighestRow() - 1;
    return $this->t("The uploaded file is @file with @total rows data.", [
      '@file' => $file->getFilename(),
      '@total' => $total,
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $storage = &$form_state->getStorage();
    if ($storage['step'] == 1) {
      $file = $form_state->getValue('file');
      if (!empty($file)) {
        $file = File::load($file['0']);
        $file->setPermanent();
        $file->save();

        $form_state->cleanValues();
        $values = $form_state->getValues();

        $storage['file'] = $file;
        $storage['form_values'] = $values;
      }
      else {
        drupal_set_message(t('No file uploaded.'), 'error');
      }
      $form_state->setRebuild();
    }
    elseif ($storage['step'] == 2) {
      $form_values = $storage['form_values'];
      $file = $storage['file'];
      // We are done with the file, remove it from storage.
      unset($storage['file']);
      $spreadsheet = IOFactory::load(drupal_realpath($file->getFileUri()));
      $sheetData = $spreadsheet->getActiveSheet()->toArray(NULL, TRUE, TRUE);

      $headers = array_shift($sheetData);
      $op = [];
      foreach ($sheetData as $index => $row) {
        $row = array_combine($headers, $row);
        $op[] = [
          [
            $this,
            'processCallback',
          ],
          [
            $row,
            $index + 1,
            $form_values,
          ],
        ];
      }
      if (!empty($op)) {
        $total = count($op);

        $batch = [
          'title' => $this->getTitle(),
          'init_message' => t('Start Import'),
          'error_message' => t('An error occurred while import.'),
          'operations' => $op,
          'finished' => [
            $this,
            'finishedCallback',
          ],
        ];
        batch_set($batch);
      }
      else {
        drupal_set_message(t('No valid data to process.', 'notice'));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
  }

  /**
   * Process data.
   */
  public static function processData($data, $index, $form_values, &$context) {
    $result = [
      'message' => '',
      'status' => 1,
    ];

    return $result;
  }

  /**
   * Process Callback.
   */
  public static function processCallback($data, $index, $form_values, &$context) {

    if (empty($context['results'])) {
      $context['results']['message'] = [];
      $context['results']['counter'] = [
        '@numitems' => 0,
        '@success' => 0,
        '@failures' => 0,
      ];
    }

    $result = self::processData($data, $index, $form_values, $context);

    $context['results']['counter']['@numitems']++;
    if (!empty($result['message'])) {
      $context['results']['message'][] = $result['message'];
    }
    if (!empty($result['status'])) {
      $context['results']['counter']['@success']++;
    }
    else {
      $context['results']['counter']['@failures']++;
    }

  }

  /**
   * Finished callback.
   */
  public static function finishedCallback($success, $results, $operations) {
    // The 'success' parameter means no fatal PHP errors were detected. All
    // other error management should be handled using 'results'.
    if ($success) {
      $singular_message = "Processed 1 item (@success success, @failures failure)";
      $plural_message = "Processed @numitems items (@success success, @failures failure)";
      drupal_set_message(\Drupal::translation()->formatPlural($results['counter']['@numitems'],
          $singular_message,
          $plural_message,
          $results['counter']));
    }
    else {
      $message = t('Finished with an error.');
      drupal_set_message($message);
    }
    if (!empty($results['message'])) {
      $process_msg = implode('<br/>', $results['message']);
      drupal_set_message(SafeMarkup::format($process_msg, []), 'error');
    }
  }

}
