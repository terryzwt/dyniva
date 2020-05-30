<?php

namespace Drupal\dyniva_permission\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Component\Utility\Xss;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Url;
use Drupal\node\NodeStorageInterface;
use Drupal\node\NodeTypeInterface;
use Drupal\node\NodeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Drupal\system\Entity\Menu;
use Drupal\user\UserInterface;
use Drupal\dyniva_permission\Form\DynivaAssignRoleForm;

/**
 * Class DynivaPermissionController.
 *
 * @package Drupal\dyniva_permission\Controller
 */
class DynivaPermissionController extends ControllerBase implements ContainerInjectionInterface {

  /**
   * add roles.
   *
   * @return string
   *   Return Hello string.
   */
  public function assignRole(UserInterface $user, $vid = NULL) {
//     $permission = $this->entityTypeManager()->getStorage('dyniva_permission')->create(array(
//       'uid' => $user->id(),
//     ));
//     return $this->entityFormBuilder()->getForm($permission);
    if(empty($vid)){
      $vid = \Drupal::state()->get('dyniva_permission.permission_vid','department');
    }
    return \Drupal::formBuilder()->getForm(DynivaAssignRoleForm::class, $user, $vid);
  }

  /**
   * Return a page title.
   */
  public function assignRoleTitle(UserInterface $user) {
    return t('Assign Roles to @name', array('@name' => $user->getDisplayName()));
  }
}
