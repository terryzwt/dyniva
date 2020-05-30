<?php

namespace Drupal\dyniva_ui_patterns_preview\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Datetime\DateFormatterInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Returns responses for UI Preview routes.
 */
class PreviewController extends ControllerBase {

  /**
   * The date formatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * Constructs the controller object.
   *
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   *   The date formatter service.
   */
  public function __construct(DateFormatterInterface $date_formatter) {
    $this->dateFormatter = $date_formatter;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('date.formatter')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function previewUiWithoutTheme($page, $html) {
    $theme = \Drupal::theme()
      ->getActiveTheme()
      ->getName();
    return $this->previewUi($theme, $page, $html);
  }

  /**
   * {@inheritdoc}
   */
  public function previewUiIndexWithoutTheme($html) {
    $theme = \Drupal::theme()
      ->getActiveTheme()
      ->getName();
    return $this->previewUiIndex($theme, $html);
  }

  /**
   * {@inheritdoc}
   */
  public function previewUi($theme, $page, $html) {
    $path = drupal_get_path('theme', $theme) . '/templates/ui/';
    if (!preg_match('/\\.html\\.twig$/', $html)) {
      $html .= '.html.twig';
    }
    $file_path = \Drupal::root() . '/' . $path . $page . '/' . $html;
    if (!\file_exists($file_path)) {
      return [
        '#markup' => $path . $html . ' not found.',
      ];
    }
    $output = [
      'html' => [
        '#type' => 'inline_template',
        '#template' => file_get_contents($file_path),
        '#context' => [
          'theme_path' => drupal_get_path('theme', $theme),
          'base_path' => base_path(),
          'theme' => base_path() . drupal_get_path('theme', $theme),
        ],
      ],
      '#cache' => ['max-age' => 0],
    ];

    $this->setTheme($theme);

    return $output;
  }

  /**
   * {@inheritdoc}
   */
  public function previewUiIndex($theme, $html) {
    $path = drupal_get_path('theme', $theme) . '/templates/ui/';
    if (!preg_match('/\\.html\\.twig$/', $html)) {
      $html .= '.html.twig';
    }
    $file_path = \Drupal::root() . '/' . $path . $html;
    if (!\file_exists($file_path)) {
      return [
        '#markup' => $path . $html . ' not found.',
      ];
    }
    $output = [
      'html' => [
        '#type' => 'inline_template',
        '#template' => file_get_contents($file_path),
        '#context' => [
          'theme_path' => drupal_get_path('theme', $theme),
          'base_path' => base_path(),
          'theme' => base_path() . drupal_get_path('theme', $theme),
        ],
      ],
      '#cache' => ['max-age' => 0],
    ];

    $this->setTheme($theme);

    return $output;
  }

  /**
   * {@inheritdoc}
   */
  private function setTheme($theme) {
    $theme_initialization = \Drupal::service('theme.initialization');
    $theme = $theme_initialization->initTheme($theme);
    \Drupal::theme()->setActiveTheme($theme);
  }

}
