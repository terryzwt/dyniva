<?php

namespace Drupal\dyniva_core;

use Endroid\QrCode\QrCode;
use Endroid\QrCode\ErrorCorrectionLevel;
use Drupal\Core\Url;
use Drupal\Component\Utility\Crypt;

/**
 * Qrcode generator.
 */
class CcmsQrCode {

  const QRCODE_FILE_DIR = 'public://qrcode';
  const QRCODE_FILE_EXT = 'png';

  /**
   * Generate form text.
   *
   * @param string $text
   *   Text.
   * @param int $size
   *   Size.
   * @param int $margin
   *   Margin.
   *
   * @return \Endroid\QrCode\QrCode
   *   Qrcode object.
   */
  public static function fromText($text, $size = 300, $margin = 10) {

    $qrCode = new QrCode($text);
    $qrCode->setSize($size);
    $qrCode->setMargin($margin);
    $qrCode->setErrorCorrectionLevel(new ErrorCorrectionLevel(ErrorCorrectionLevel::HIGH));

    // Set advanced options
    // $qrCode->setWriterByName(self::QRCODE_FILE_EXT);
    // $qrCode->setMargin($margin);
    // $qrCode->setEncoding('UTF-8');
    // $qrCode->setErrorCorrectionLevel(ErrorCorrectionLevel::HIGH);
    // $qrCode->setForegroundColor(['r' => 0, 'g' => 0, 'b' => 0]);
    // $qrCode->setBackgroundColor(['r' => 255, 'g' => 255, 'b' => 255]);
    // $qrCode->setLabel('Scan the code', 16, __DIR__.'/../assets/fonts/noto_sans.otf', LabelAlignment::CENTER);
    // $qrCode->setLogoPath(__DIR__.'/../assets/images/symfony.png');
    // $qrCode->setLogoWidth(150);
    // $qrCode->setValidateResult(false);
    return $qrCode;
  }

  /**
   * Generate from text.
   *
   * @param string $text
   *   Text.
   * @param string $file_name
   *   File name.
   * @param int $size
   *   Size.
   * @param int $margin
   *   Margin.
   *
   * @return string
   *   File path.
   */
  public static function fileFromText($text, $file_name = NULL, $size = 300, $margin = 10) {

    if (!$file_name) {
      $file_name = date("YmdHis") . rand(0, 1000) . '.png';
    }
    else {
      $file_name .= '.' . self::QRCODE_FILE_EXT;
    }
    $file_path = self::QRCODE_FILE_DIR . '/' . $file_name;

    if (file_exists($file_path)) {
      return $file_path;
    }
    if (!file_exists(self::QRCODE_FILE_DIR)) {
      drupal_mkdir(self::QRCODE_FILE_DIR);
    }

    $qrCode = self::fromText($text, $size, $margin);

    $qrCode->writeFile($file_path);

    return $file_path;
  }

  /**
   * Generate from current url.
   *
   * @param int $size
   *   Size.
   * @param int $margin
   *   Margin.
   *
   * @return string
   *   File path.
   */
  public static function fromCurrentUrl($size = 300, $margin = 10) {
    $current_url = Url::fromRoute('<current>');
    $path = $current_url->setAbsolute()->toString();
    $file_name = Crypt::hashBase64($path);

    return self::fileFromText($path, $file_name);
  }

}
