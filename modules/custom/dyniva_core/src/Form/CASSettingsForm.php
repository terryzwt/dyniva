<?php

namespace Drupal\dyniva_core\Form;

use Drupal\Core\Url;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Cas settings form.
 */
class CASSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'dyniva_core_cas_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'dyniva_core.cas.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->configFactory()->get('dyniva_core.cas.settings');

    $form['enable'] = [
      '#type' => 'checkbox',
      '#title' => t('Enable CAS Login'),
      '#default_value' => $config->get('enable'),
    ];

    $form['title'] = [
      '#type' => 'textfield',
      '#title' => t('Title'),
      '#default_value' => $config->get('title') ? $config->get('title') : '中央认证服务(CAS)登录',
    ];
    $form['subtitle'] = [
      '#type' => 'textfield',
      '#title' => t('Subtitle'),
      '#default_value' => $config->get('subtitle') ? $config->get('subtitle') : '管理人员通过NetID',
    ];

    $default_url = '';
    if (\Drupal::moduleHandler()->moduleExists('cas')) {
      $default_url = Url::fromRoute('cas.login')->toString();
    }

    $form['url'] = [
      '#type' => 'textfield',
      '#title' => t('CAS Link'),
      '#default_value' => $config->get('url') ? $config->get('url') : $default_url,
      '#required' => TRUE,
    ];

    $form['username_exists_rename'] = [
      '#type' => 'checkbox',
      '#title' => 'Username exists rename',
      '#default_value' => $config->get('username_exists_rename'),
    ];

    $form['actions'] = [
      '#type' => 'actions',
      '#attributes' => [
        'class' => ['clearfix'],
      ],
    ];

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $config = $this->configFactory()->getEditable('dyniva_core.cas.settings');
    if ($values['enable'] != $config->get('enable')) {
      $plugin_id = 'cas_login';
      $block_id = 'dyniva_core_cas_login';
      $block = \Drupal::entityManager()->getStorage('block')->load($block_id);
      if ($values['enable'] && !$block) {
        // Add block into region.
        $theme = 'dyniva_admin';
        $entity = \Drupal::entityManager()->getStorage('block')->create([
          'plugin' => $plugin_id,
          'theme' => $theme,
        ]);
        $entity->setRegion('user_slide');
        $entity->set('id', $block_id);
        $entity->set('settings', [
          'id' => $block_id,
          'label' => 'CAS Login',
          'provider' => 'dyniva_core',
          'label_display' => 0,
        ]);
        $entity->save();
      }
      if (!$values['enable'] && $block) {
        // Delete block.
        $block->delete();
      }
    }
    foreach (['title', 'subtitle', 'url', 'enable', 'username_exists_rename'] as $key) {
      $config->set($key, $values[$key]);
    }

    $config->save();
    parent::submitForm($form, $form_state);
  }

}
