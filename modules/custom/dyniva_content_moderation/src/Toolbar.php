<?php

namespace Drupal\dyniva_content_moderation;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Drupal\workflows\WorkflowTypeInterface;
use Drupal\workflows\WorkflowInterface;
use Drupal\dyniva_core\Plugin\ManagedEntityPluginManager;


/**
 * Class Toolbar.
 *
 * @package Drupal\workspace_ccms
 */
class Toolbar {
	use StringTranslationTrait;

	/**
	 * Constructs a new Toolbar.
	 *
	 */
	public function __construct() {
	}

	/**
	 * Hook bridge;  Responds to hook_toolbar().
	 *
	 * @see hook_toolbar().
	 */
	public function toolbar() {
		$items = [];

		// Display item on moderation entity.
		$node = \Drupal::routeMatch()->getParameter('node');
		if (is_numeric($node)) {
		  $node = \Drupal\node\Entity\Node::load($node);
		}
		if (empty($node) || !$node instanceof \Drupal\node\Entity\Node) {
			return [];
		}
		$moderationInfo = \Drupal::service('content_moderation.moderation_information');
		/**
		 * 
		 * @var ManagedEntityPluginManager $plugin_manager
		 */
		$plugin_manager = \Drupal::service('plugin.manager.managed_entity_plugin');
		if (!empty($node)) {
		  $managedEntity = dyniva_core_get_entity_managed_entity($node);
		  $current_path = \Drupal::service('path.current')->getPath();
			if ($managedEntity && $moderationInfo->isModeratedEntity($node)) {
			  $edit = Url::fromRoute('dyniva_core.managed_entity.' . $managedEntity->id() . '.edit_page', ['managed_entity_id' => $node->id()]);
			  $access = \Drupal::accessManager()->checkNamedRoute($edit->getRouteName(),$edit->getRouteParameters());
			  if(!$access){
			    return $items;
			  }
			  $links = [];
			  if($plugin_manager->isPluginEnable($managedEntity, 'moderation')){
  				$moderate = Url::fromRoute('dyniva_core.managed_entity.' . $managedEntity->id() . '.moderation_page', ['managed_entity_id' => $node->id()]);
  				
  				$items['moderation_state'] = [
  				  '#type' => 'toolbar_item',
  				  '#weight' => 123,
  				  '#wrapper_attributes' => [
  				    'class' => ['workspace-toolbar-tab'],
  				  ],
  				  '#attached' => [
  				    'library' => [
  				      'toolbar/toolbar',
  				    ],
  				  ],
  				];
  				
  				
  				$moderation_state = $node->moderation_state->value;
  				/**
  				 *
  				 * @var WorkflowInterface $workflow
  				 */
  				$workflow = \Drupal::service('content_moderation.moderation_information')->getWorkflowForEntity($node);
  				if (!$moderation_state) {
  				  $moderation_state = $workflow->getTypePlugin()->getInitialState($node)->id();
  				}
  				$status = $workflow->getTypePlugin()->getState($moderation_state);
  				$items['moderation_state']['tab'] = [
  				  '#type' => 'link',
  				  '#title' => $this->t($status->label()),
  				  '#url' => $moderate,
  				  '#attributes' => [
  				    'title' => $this->t('Moderation State'),
  				    'class' => ['toolbar-icon'],
  				  ],
  				];
  				
  				$links['moderate'] = [
  				  'title' => $this->t('Moderate'),
  				  'url' => $moderate,
  				  'attributes' => [
  				    'class' => [
  				      'link-to-moderate',
  				      $current_path == $moderate ? 'is-active' : ''
  				    ],
  				  ]
			     ];
			  }
			  if($plugin_manager->isPluginEnable($managedEntity, 'revision')){
  				$revision = Url::fromRoute('dyniva_core.managed_entity.' . $managedEntity->id() . '.revision_page', ['managed_entity_id' => $node->id()]);
  				$links['revesions'] = [
  				  'title' => $this->t('Revisions'),
  				  'url' => $revision,
  				  'attributes' => [
  				    'class' => [
  				      'link-to-revisions',
  				      $current_path == $revision ? 'is-active' : ''
  				    ],
  				  ],
  				];
			  }
				
				$items['moderation_state']['tray'] = [
					'#heading' => $this->t('Moderate state actions'),
					'moderate_link' => [
						'#links' => $links,
						'#theme' => 'links__toolbar_user',
						'#attributes' => [
							'class' => ['toolbar-menu', 'toolbar-moderate-actions'],
						],      			
					],
				];
			}
			$items['#cache'] = [
			  'context' => ['user'],
			  'tags' => ["{$node->getEntityTypeId()}:{$node->id()}"],
			];
		}
		return $items;
	}
}
