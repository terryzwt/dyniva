<?php

namespace Drupal\dyniva_content_moderation\Plugin\views\field;

use Drupal\Core\Url;
use Drupal\views\Plugin\views\field\LinkBase;
use Drupal\views\ResultRow;
use Drupal\node\Entity\Node;

/**
 * Field handler to present a link to a node revisions.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("node_revisions_link")
 */
class RevisionsLink extends LinkBase {

  /**
   * {@inheritdoc}
   */
  protected function getUrlInfo(ResultRow $row) {
    $node = $this->getEntity($row);
    if ($managedEntity = dyniva_core_get_entity_managed_entity($node)) {
      return Url::fromRoute("dyniva_core.managed_entity.{$managedEntity->id()}.revision_page", [
        'managed_entity_id' => $node->id(),
        'managed_entity' => $managedEntity->id(),
        'plugin_id' => 'revision'
    
      ]);
    }
    if($node instanceof Node){
      return Url::fromRoute('ccms.entity.node.version_history', ['node' => $node->id()]);
    }else{
      return Url::fromRoute('entity.' . $node->getEntityTypeId() . '.revision', [$node->getEntityTypeId() => $node->id()]);
    }
    
  }

  /**
   * {@inheritdoc}
   */
  protected function getDefaultLabel() {
    return $this->t('Revisions');
  }

}
