<?php

namespace Drupal\dyniva_core\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\node\NodeTypeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\system\Entity\Menu;
use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\taxonomy\Form\OverviewTerms;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Class CcmsCoreController.
 *
 * @package Drupal\dyniva_core\Controller
 */
class CcmsCoreController extends ControllerBase implements ContainerInjectionInterface {

  /**
   * The date formatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * The renderer service.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * Constructs a NodeController object.
   *
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   *   The date formatter service.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer service.
   */
  public function __construct(DateFormatterInterface $date_formatter, RendererInterface $renderer) {
    $this->dateFormatter = $date_formatter;
    $this->renderer = $renderer;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('date.formatter'),
      $container->get('renderer')
    );
  }

  /**
   * Add Content.
   *
   * @param \Drupal\node\NodeTypeInterface $node_type
   *   Node type.
   *
   * @return array
   *   Render array.
   */
  public function add(NodeTypeInterface $node_type) {
    $node = $this->entityManager()->getStorage('node')->create([
      'type' => $node_type->id(),
    ]);
    return $this->entityFormBuilder()->getForm($node);
  }

  /**
   * Return a page title.
   *
   * @param \Drupal\node\NodeTypeInterface $node_type
   *   Node type.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   *   Title.
   */
  public function addTitle(NodeTypeInterface $node_type) {
    return t('Create @name', ['@name' => $node_type->label()]);
  }

  /**
   * Mnage menu form.
   *
   * @param \Drupal\system\Entity\Menu $menu
   *   Menu entity.
   *
   * @return array
   *   Render array.
   */
  public function manageMenu(Menu $menu) {
    $form = \Drupal::service('entity.form_builder')->getForm($menu, 'edit');
    unset($form['actions']['delete']);
    return $form;
  }

  /**
   * Manage taxonomy page.
   *
   * @param string $vid
   *   Vocabulary vid.
   *
   * @return array
   *   Render array.
   */
  public function manageTaxonomy($vid) {
    $v = Vocabulary::load($vid);
    $form = \Drupal::formBuilder()->getForm(OverviewTerms::class, $v);
    return $form;
  }

  /**
   * Manage taxonomy page title.
   *
   * @param string $vid
   *   Vocabulary vid.
   *
   * @return string
   *   Title.
   */
  public function manageTaxonomyTitle($vid=null) {
    if($vid) {
      $v = Vocabulary::load($vid);
      return t($v->label());
    }
    return '';
  }

  /**
   * Manage taxonomy page access.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   Account object.
   * @param string $vid
   *   Vocabulary vid.
   *
   * @return \Drupal\Core\Access\AccessResult
   *   Access result.
   */
  public function manageTaxonomyAccess(AccountInterface $account, $vid) {
    return AccessResult::allowedIfHasPermission($account, "edit terms in {$vid}");
  }

  /**
   * Preview entity page.
   *
   * @param string $entity_type_id
   *   Entity type id.
   * @param string $entity_id
   *   Entity id.
   *
   * @return string[]|string[][]|array[]|\Drupal\Core\StringTranslation\TranslatableMarkup[]
   *   Render array.
   */
  public function previewEntity($entity_type_id, $entity_id) {
    // Replace - in url.
    $entity_type_id = preg_replace('/-/', '_', $entity_type_id);

    $entity = $this->entityTypeManager()->getStorage($entity_type_id)->load($entity_id);
    if ($entity) {
      $render = $this->entityTypeManager()->getViewBuilder($entity_type_id)->view($entity);
      $block = [
        '#theme' => 'block',
        '#configuration' => [
          'provider' => 'dyniva_core_preview',
        ],
        '#plugin_id' => 'dyniva_core_preview',
        '#base_plugin_id' => 'dyniva_core_preview',
        '#derivative_plugin_id' => 'dyniva_core_preview',
        'content' => $render,
      ];
      return $block;
    }
    return ['#markup' => $this->t('No preview content')];
  }

}
