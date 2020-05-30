<?php

namespace Drupal\dyniva_core;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * 
 * @author ziqiang
 * 
 * @usage
 * Data struct
 * single sheet
 * $data = [
 *    ['标题','内容'],
 *    [1,2,3,4],
 *    [5,6,7,8],
 *  ];
 *  $response = \Drupal\dyniva_core\CcmsExportHelper::getResponse('demo', $data);
 *  
 * multip sheet
 * $data = [
 *    'sheet1' => [
 *      ['标题','内容'],
 *      [1,2,3,4],
 *      [5,6,7,8],
 *    ],
 *    'sheet2' => [
 *      ['标题','内容'],
 *      [1,2,3,4],
 *      [5,6,7,8],
 *    ],
 *  ];
 *  $response = \Drupal\dyniva_core\CcmsExportHelper::getResponse('demo', $data, true);
 *  
 * Get http response form array data:
 * $response = \Drupal\dyniva_core\CcmsExportHelper::getResponse('demo', $data);
 * 
 * Get spreadsheet and set style:
 * $spreadsheet = \Drupal\dyniva_core\CcmsExportHelper::getSpreadsheet($data);
 * $spreadsheet->getActiveSheet()->getStyle('A7:B7')->getFont()->setBold(true)->setName('Arial')->setSize(10);
 * $spreadsheet->getActiveSheet()->getStyle('A4')->getFont()->getColor()->setARGB(\PhpOffice\PhpSpreadsheet\Style\Color::COLOR_RED);
 * $response = \Drupal\dyniva_core\CcmsExportHelper::getResponseFromSpreadsheet('demo', $spreadsheet);
 *
 */
class CcmsExportHelper {

  /**
   * Get response from array data.
   * 
   * @param string $file_name
   * @param array $data
   * @param string $multi_sheet
   * @return \Symfony\Component\HttpFoundation\Response
   */
  public static function getResponse($file_name, array $data = [], $multi_sheet = false) {
    $spreadsheet = static::getSpreadsheet($data, $multi_sheet);
    $response = static::getResponseFromSpreadsheet($file_name, $spreadsheet);
    return $response;
  }
  /**
   * Get response from spreadsheet.
   * 
   * @param string $file_name
   * @param PhpOffice\PhpSpreadsheet\Spreadsheet $spreadsheet
   * @return \Symfony\Component\HttpFoundation\Response
   */
  public static function getResponseFromSpreadsheet($file_name, $spreadsheet) {
    $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
    $response =  new StreamedResponse(
        function () use ($writer) {
          $writer->save('php://output');
        }
        );
    $response->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet; charset=utf-8');
    $response->headers->set('Content-Disposition', 'attachment;filename="' . $file_name . '.xlsx"');
    $response->headers->set('Cache-Control','max-age=0');
    
    return $response;
  }

  /**
   * Get spreadsheet from array data.
   *
   * @param array $data
   * @param boolean $multi_sheet
   * @return \PhpOffice\PhpSpreadsheet\Spreadsheet
   */
  public static function getSpreadsheet(array $data = [], $multi_sheet = false) {
    $spreadsheet = new Spreadsheet();
    
    if (!$multi_sheet) {
      $data = [
        $data
      ];
    }
    
    $sheet_index = 0;
    foreach ($data as $s_title => $sheet) {
      $spreadsheet->createSheet($sheet_index);
      $worksheet = $spreadsheet->getSheet($sheet_index);
      if ($multi_sheet) {
        $worksheet->setTitle($s_title);
      }
      foreach ($sheet as $r_index => $row) {
        if (!is_numeric($r_index))
          continue;
        foreach ($row as $c_index => $value) {
          if (!is_numeric($c_index))
            continue;
          $worksheet->setCellValueByColumnAndRow($c_index+1, $r_index+1, $value);
        }
      }
      $sheet_index++;
    }
    $spreadsheet->setActiveSheetIndex(0);
    
    return $spreadsheet;
  }

}
