<?php

namespace Drupal\dyniva_content_moderation\Plugin\views\field;

use Drupal\Core\Url;
use Drupal\views\Plugin\views\field\LinkBase;
use Drupal\views\ResultRow;

/**
 * Field handler to present a link to moderate a node.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("node_edit_link")
 */
class EditLink extends LinkBase {

  /**
   * {@inheritdoc}
   */
  protected function getUrlInfo(ResultRow $row) {
    $node = $this->getEntity($row);
    if ($managedEntity = dyniva_core_get_entity_managed_entity($node)) {
      return Url::fromRoute("dyniva_core.managed_entity.{$managedEntity->id()}.edit_page", [
        'managed_entity_id' => $node->id(),
        'managed_entity' => $managedEntity->id(),
        
      ]);
    }
    return Url::fromUserInput("node/{$node->id()}/edit");
  }

  /**
   * {@inheritdoc}
   */
  protected function getDefaultLabel() {
    return $this->t('Edit');
  }

}
