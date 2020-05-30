<?php

/**
 * Implements hook_views_data().
 */
function dyniva_permission_views_data_alter(array &$data) {
  
  $data['node_field_data']['dyniva_permission'] = [
    'filter' => [
      'title' => t('Filter Permisson'),
      'id' => 'dyniva_permission',
    ]
  ];
  $data['views']['filter_author_by_permission'] = [
    'filter' => [
      'title' => t('Filter Author by Permisson'),
      'id' => 'filter_author_by_permission',
    ]
  ];
  $data['views']['filter_author_by_scope'] = [
    'filter' => [
      'title' => t('Filter Author by Scope Parameter'),
      'id' => 'filter_author_by_scope',
    ]
  ];
  $data['media_field_data']['dyniva_permission'] = [
    'filter' => [
      'title' => t('Filter Permisson'),
      'id' => 'dyniva_permission',
    ]
  ];
}
