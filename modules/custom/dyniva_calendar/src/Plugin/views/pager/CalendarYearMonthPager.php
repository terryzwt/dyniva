<?php

namespace Drupal\dyniva_calendar\Plugin\views\pager;

use Drupal\calendar\CalendarHelper;
use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\views\Plugin\views\pager\PagerPluginBase;
use Drupal\views\ViewExecutable;

/**
 * The plugin to handle calendar pager.
 *
 * @ingroup views_pager_plugins
 *
 * @ViewsPager(
 *   id = "calendar_year_month_pager",
 *   title = @Translation("Calendar Year Month Pager"),
 *   short_title = @Translation("Calendar"),
 *   help = @Translation("Calendar Year Month Pager"),
 *   theme = "calendar_year_month_pager",
 *   register_theme = FALSE
 * )
 */
class CalendarYearMonthPager extends PagerPluginBase {

  const NEXT = '+';
  const PREVIOUS = '-';
  /**
   * Date argument.
   *
   * @var \Drupal\calendar\DateArgumentWrapper
   */
  protected $argument;

  /**
   * {@inheritdoc}
   */
  public function init(ViewExecutable $view, DisplayPluginBase $display, array &$options = NULL) {
    parent::init($view, $display, $options);
    $this->argument = CalendarHelper::getDateArgumentHandler($this->view);
    $this->setItemsPerPage(0);
  }

  /**
   * {@inheritdoc}
   */
  public function render($input) {
    if (!$this->argument->validateValue()) {
      return [];
    }
    $path = \Drupal::service('path.current')->getPath();
    $current = '';
    preg_match('/\/\d{1,6}/', $path, $matches);
    if (!empty($matches[0])) {
      $current = str_replace('/', '', $matches[0]);
      $currenty = substr($current, 0, 4);
      $currentm = substr($current, -2);
    }
    $month = date('m');
    $year = date('Y');
    for ($i = (int) $year - 3; $i <= (int) $year + 3; $i++) {
      $optionsyear[$i] = $i;
    }
    foreach ($optionsyear as $y) {
      if ((empty($current) && $y == $year) || ($current && $y == $currenty)) {
        $items['year'][] = ['selected' => 1, 'y' => $y];
      }
      else {
        $items['year'][] = ['selected' => 0, 'y' => $y];
      }
    }

    $optionsmonth = [
      '01',
      '02',
      '03',
      '04',
      '05',
      '06',
      '07',
      '08',
      '09',
      '10',
      '11',
      '12',
    ];
    foreach ($optionsmonth as $m) {
      if ((empty($current) && $m == $month) || ($current && $m == $currentm)) {
        $items['month'][] = ['selected' => 1, 'm' => $m];
      }
      else {
        $items['month'][] = ['selected' => 0, 'm' => $m];
      }
    }
    return [
      '#theme' => $this->themeFunctions(),
      '#items' => $items,
      '#exclude' => $this->options['exclude_display'],
    ];
  }

  /**
   * Get the date argument value for the pager link.
   *
   * @param unknown $mode
   *   Either '-' or '+' to determine which direction.
   *
   * @return string
   *   The textual output generated.
   */
  protected function getPagerArgValue(unknown $mode) {
    $datetime = $this->argument->createDateTime();
    $datetime->modify($mode . '1 ' . $this->argument->getGranularity());
    return $datetime->format($this->argument->getArgFormat());
  }

  /**
   * Get the href value for the pager link.
   *
   * @param unknown $mode
   *   Either '-' or '+' to determine which direction.
   *
   * @return string
   *   The textual output generated.
   */
  protected function getPagerUrl(unknown $mode) {
    $value = $this->getPagerArgValue($mode);
    $current_position = 0;
    $arg_vals = [];
    /*
     * @var \Drupal\views\Plugin\views\argument\ArgumentPluginBase $handler
     */
    foreach ($this->view->argument as $name => $handler) {
      if ($current_position != $this->argument->getPosition()) {
        $arg_vals["arg_$current_position"] = $handler->getValue();
      }
      else {
        $arg_vals["arg_$current_position"] = $value;
      }
      $current_position++;
    }

    return $this->view->getUrl($arg_vals, $this->view->current_display);
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);
    $form['exclude_display'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Exclude from Display'),
      '#default_value' => $this->options['exclude_display'],
      '#description' => $this->t('Use this option if you only want to display the pager in Calendar Header area.'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['exclude_display'] = ['default' => FALSE];

    return $options;
  }

}
