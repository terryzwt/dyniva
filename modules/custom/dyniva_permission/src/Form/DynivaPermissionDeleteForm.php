<?php

namespace Drupal\dyniva_permission\Form;

use Drupal\Core\Entity\ContentEntityDeleteForm;
use Drupal\Core\Url;

/**
 * Provides a form for deleting entities.
 *
 * @ingroup dyniva_permission
 */
class DynivaPermissionDeleteForm extends ContentEntityDeleteForm {

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    $entity = $this->getEntity();
    return Url::fromRoute('view.user_manage_roles.page_list',['user' => $entity->uid->target_id]);
  }
  /**
   * {@inheritdoc}
   */
  protected function getRedirectUrl() {
    $entity = $this->getEntity();
    return Url::fromRoute('view.user_manage_roles.page_list',['user' => $entity->uid->target_id]);
  }
}
