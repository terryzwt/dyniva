<?php

namespace Drupal\dyniva_message\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\Cache;
use Drupal\views\Views;

/**
 * User info block.
 *
 * @Block(
 *  id = "dyniva_message_toolbar",
 *  admin_label = @Translation("Message Notification Toolbar"),
 * )
 */
class Toolbar extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = [];
    $view = Views::getView('dyniva_notifications');
    if($view){
      $view->setDisplay('unread');
      $view->preExecute();
      $view->execute();
      $notices = [];
      foreach ($view->result as $value) {
        $message = $value->_entity;
        $link = '';
        
        if($message->hasField('content_id') && !$message->content_id->isEmpty()) {
          $node = \Drupal::service('entity.manager')->getStorage('node')->load($message->content_id->value);
          if($node) {
            $link = $node->toUrl()->toString();
          }
        }
    
        if($message->hasField('comment_id') && !$message->comment_id->isEmpty()) {
          $comment = \Drupal::service('entity.manager')->getStorage('comment')->load($message->comment_id->value);
          if($comment && $comment->entity_id->target_id) {
            $node = \Drupal::service('entity.manager')->getStorage('node')->load($comment->entity_id->target_id);
            if($node) {
              $link = $node->toUrl()->toString();
            }
          }
        }
        $view_builder = \Drupal::service('entity.manager')->getViewBuilder($message->getEntityTypeId());
        $_view = $view_builder->view($message, 'default');
        $rendered = \Drupal::service('renderer')
          ->render($_view);
        $rendered = trim(strip_tags($rendered));
        $notices []= [
          'text' => $rendered,
          'link' => $link,
          'time' => $message->created->value
        ];
      }
      $build['#attached']['drupalSettings']['dyniva_message']['unread_notices'] = $notices;
    
      $site_name = \Drupal::service('config.factory')->get('system.site')->get('name');
      $build['#attached']['drupalSettings']['dyniva_message']['site_name'] = $site_name;
      $build['#attached']['library'][] = 'dyniva_message/notifications';
    
      $user = user_load(\Drupal::currentUser()->id());
      $build['#attached']['drupalSettings']['dyniva_message']['browser_notification'] = false;
      if($user->hasField('notifiers')) {
        $build['#attached']['drupalSettings']['dyniva_message']['browser_notification'] = $this->any($user->notifiers->getValue(), function($item) {
          if(!empty($item['value'])) return $item['value'] == 'browser_notification';
          return false;
        }) ? 1 : 0;
      }
    }


    $build['#theme'] = 'dyniva_message_toolbar';
    // $build['#cache'] = [
    //   'contexts' => ['user'],
    // ];
    $build['#cache']['max-age'] = 0;
    $build['#cache']['contexts'] = [];
    $build['#cache']['tags'] = [];
    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    $cache_contexts = parent::getCacheContexts();
    $cache_contexts = Cache::mergeContexts($cache_contexts, ['user']);
    return $cache_contexts;
  }
  
  private function any($items, $func)
  {
    return count(array_filter($items, $func)) > 0;
  }

}
