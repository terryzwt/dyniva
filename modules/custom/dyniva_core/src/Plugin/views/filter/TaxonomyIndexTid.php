<?php

namespace Drupal\dyniva_core\Plugin\views\filter;

use Drupal\Core\Form\FormStateInterface;
use Drupal\taxonomy\Plugin\views\filter\TaxonomyIndexTid as TaxonomyIndexTidBase;
use Drupal\views\ViewExecutable;
use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\dyniva_core\Plugin\views\ManyToOneHelper;

/**
 * Filter by term id.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("ccms_taxonomy_index_tid")
 */
class TaxonomyIndexTid extends TaxonomyIndexTidBase {

  /**
   * {@inheritdoc}
   */
  public function init(ViewExecutable $view, DisplayPluginBase $display, array &$options = NULL) {
    parent::init($view, $display, $options);

    $this->helper = new ManyToOneHelper($this);
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();

    $options['with_depth'] = ['default' => FALSE];

    return $options;
  }

  /**
   * Builder options form.
   *
   * {@inheritDoc}
   */
  public function buildExtraOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildExtraOptionsForm($form, $form_state);

    $form['with_depth'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Filter by taxonomy depth'),
      '#default_value' => !empty($this->options['with_depth']),
    ];
  }

}
