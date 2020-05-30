<?php

namespace Drupal\dyniva_private_message\Plugin\Field\FieldFormatter;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Url;
use Drupal\private_message\Plugin\Field\FieldFormatter\PrivateMessageThreadMemberFormatter as SuperPrivateMessageThreadMemberFormatter;

class PrivateMessageThreadMemberFormatter extends SuperPrivateMessageThreadMemberFormatter {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $element = [];

    $access_profiles = $this->currentUser->hasPermission('access user profiles');
    $users = [];

    $view_builder = $this->entityManager->getViewBuilder('user');
    $i = 0;
    foreach ($items as $delta => $item) {
      if($i++ > 20) break;
      $user = $item->entity;
      if ($user) {
        if ($user->id() != $this->currentUser->id()) {
          if ($this->getSetting('display_type') == 'label') {
            if ($access_profiles) {
              $url = Url::fromRoute('entity.user.canonical', ['user' => $user->id()]);
              $users[$user->id()] = new FormattableMarkup('<a href=":link">@username</a>', [':link' => $url->toString(), '@username' => $user->getDisplayName()]);
            }
            else {
              $users[$user->id()] = $user->getDisplayName();
            }
          }
          elseif ($this->getSetting('display_type') == 'entity') {
            $renderable = $view_builder->view($user, $this->getSetting('entity_display_mode'));
            $users[$user->id()] = render($renderable);
          }
        }
      }
      else {
        $users['Missing-' . $delta] = '<em>' . $this->t('User Deleted') . '</em>';
      }
    }

    $element = [
      '#prefix' => '<div class="private-message-recipients">',
      '#suffix' => '</div>',
      '#markup' => '',
    ];

    $members_prefix = $this->getSetting('members_prefix');
    if (strlen($members_prefix)) {
      $element['#markup'] .= '<span>' . $members_prefix . ' </span>';
    }

    $separator = $this->getSetting('display_type') == 'label' ? ', ' : '';
    $element['#markup'] .= implode($separator, $users);

    return $element;
  }

}
