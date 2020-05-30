<?php

namespace Drupal\dyniva_editor_panelizer\Plugin\Block;

use Drupal\block_content\Plugin\Block\BlockContentBlock as CoreBlockContent;
use Drupal\Core\Form\FormStateInterface;

/**
 * {@inheritdoc}
 */
class BlockContentBlock extends CoreBlockContent {

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);
    // Hide display title in block config.
    $form['label_display']['#access'] = FALSE;
    $form['label_display']['#default_value'] = NULL;

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form = parent::blockForm($form, $form_state);
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    parent::blockSubmit($form, $form_state);
    $config = $this->getConfiguration();
    $uuid = $this->getDerivativeId();
    /** @var \Drupal\block_content\Entity\BlockContent $entity */
    $entity = \Drupal::service('entity.manager')->loadEntityByUuid('block_content', $uuid);
    $this->configuration['vid'] = $entity->getRevisionId();
  }

  /**
   * {@inheritdoc}
   */
  protected function getEntity() {
    $uuid = $this->getDerivativeId();
    if (!isset($this->blockContent)) {
      $config = $this->getConfiguration();
      if (!empty($config['vid'])) {
        $vid = $config['vid'];
        $this->blockContent = \Drupal::service('entity.manager')->getStorage('block_content')->loadRevision($vid);
      }
      if (empty($this->blockContent)) {
        $this->blockContent = \Drupal::service('entity.manager')->loadEntityByUuid('block_content', $uuid);
      }
    }
    return $this->blockContent;
  }

  /**
   * {@inheritdoc}
   */
  public function getBlockContentEntity() {
    return $this->getEntity();
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = parent::build();
    $entity = $this->getEntity();
    if (!empty($entity->shared_type->value)) {
      $build['#attributes']['class'][] = $entity->shared_type->value;
    }
    return $build;
  }

}
