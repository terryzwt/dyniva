<?php

namespace Drupal\dyniva_core;

use Drupal\Component\Transliteration\TransliterationInterface;
use Drupal\Component\Utility\Unicode;
use Drupal\Core\Config\ConfigFactoryInterface;

/**
 * File name processer.
 */
class FilenamePostprocessor {

  /**
   * Config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Transliteration.
   *
   * @var \Drupal\Component\Transliteration\TransliterationInterface
   */
  protected $transliteration;

  /**
   * Construct.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   Config factory.
   * @param \Drupal\Component\Transliteration\TransliterationInterface $transliteration
   *   Transliteration.
   */
  public function __construct(ConfigFactoryInterface $config_factory, TransliterationInterface $transliteration) {
    $this->configFactory = $config_factory;
    $this->transliteration = $transliteration;
  }

  /**
   * Process.
   *
   * @param string $filename
   *   File name.
   *
   * @return string
   *   Translated file name.
   */
  public function process($filename) {
    $filename = Unicode::strtolower($filename);
    $filename = str_replace(' ', '_', $filename);
    $filename = $this->transliteration->transliterate($filename);

    return $filename;
  }

}
