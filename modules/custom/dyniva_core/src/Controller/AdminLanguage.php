<?php

namespace Drupal\dyniva_core\Controller;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\content_translation\ContentTranslationManagerInterface;
use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base class for entity translation controllers.
 */
class AdminLanguage extends ControllerBase {

  /**
   * The content translation manager.
   *
   * @var \Drupal\content_translation\ContentTranslationManagerInterface
   */
  protected $manager;

  /**
   * Initializes a content translation controller.
   *
   * @param \Drupal\content_translation\ContentTranslationManagerInterface $manager
   *   A content translation manager instance.
   */
  public function __construct(ContentTranslationManagerInterface $manager) {
    $this->manager = $manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('content_translation.manager'));
  }

  /**
   * Rdirect user.
   */
  public function switch($langcode) {
    $account = \Drupal::currentUser();
    $storage = \Drupal::service('entity.manager')->getStorage('user');
    $user = $storage->load($account->id());
    $user->preferred_admin_langcode->value = $langcode;
    $user->save();
    $request = \Drupal::request();
    return new RedirectResponse($request->get('destination', '/user'));
  }

}
