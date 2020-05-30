<?php

namespace Drupal\dyniva_permission\Entity;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\user\UserInterface;
use Drupal\user\Entity\User;
use Drupal\user\Entity\Role;

/**
 * Defines the Deployment entity entity.
 *
 * @ingroup ccms_deploy
 *
 * @ContentEntityType(
 *   id = "dyniva_permission",
 *   label = @Translation("Dyniva Permission entity"),
 *   handlers = {
 *     "storage_schema" = "Drupal\dyniva_permission\DynivaPermissionStorageSchema",
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "default" = "Drupal\dyniva_permission\Form\DynivaPermissionForm",
 *       "add" = "Drupal\dyniva_permission\Form\DynivaPermissionForm",
 *       "edit" = "Drupal\dyniva_permission\Form\DynivaPermissionForm",
 *       "delete" = "Drupal\dyniva_permission\Form\DynivaPermissionDeleteForm",
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\dyniva_permission\DynivaPermissionHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "dyniva_permission",
 *   translatable = FALSE,
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "id",
 *     "uid" = "cuid",
 *   },
 *   links = {
 *     "delete-form" = "/manage/permission/{dyniva_permission}/delete",
 *   },
 * )
 */
class DynivaPermission extends ContentEntityBase{
  
  /**
   * {@inheritdoc}
   */
  public static function preCreate(EntityStorageInterface $storage_controller, array &$values) {
    parent::preCreate($storage_controller, $values);
    $values += array(
      'cuid' => \Drupal::currentUser()->id(),
    );
  }
  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage) {
    if(empty($this->vid)){
      $this->vid->target_id = $this->tid->entity->vid;
    }
    parent::preSave($storage);    
  }
  /**
   * {@inheritdoc}
   */
  public static function postDelete(EntityStorageInterface $storage, array $entities) {
    parent::postDelete($storage, $entities);
    
    foreach ($entities as $entity){
      // assign role to user
      if (isset($entity->uid->target_id)) {
        $user = $entity->uid->entity;
        self::updateUserRoles($user);
      }
    }
  }
  
  /**
   * {@inheritdoc}
   */
  public function postSave(EntityStorageInterface $storage, $update = TRUE) {
    parent::postSave($storage, $update);
    
    // assign role to user
    if (isset($this->uid->target_id)) {
      $user = $this->uid->entity;
      self::updateUserRoles($user);
    }
  }
  /**
   * 
   * @param User $user
   */
  public static function updateUserRoles(User $user){
    if($user->hasRole('administrator')) return;
    
    $query = \Drupal::database()->select('dyniva_permission','t');
    $query->condition('uid',$user->id());
    $query->addField('t', 'rid');
    $added_roles = array_keys($query->execute()->fetchAllAssoc('rid'));
    $cur_roles = $user->getRoles(TRUE);
    
    $unassigned = array_diff($cur_roles,$added_roles);
    $custom_locked_roles = \Drupal::state()->get('dyniva_permission.locked_roles',[]);
    foreach ($unassigned as $rid){
      $role = Role::load($rid);
      if(!in_array($rid,$custom_locked_roles) && !$role->hasPermission('by pass ccms premission')){
        $user->removeRole($rid);
      }
    }
    $assigned = array_diff($added_roles,$cur_roles);
    foreach ($assigned as $rid){
      $user->addRole($rid);
    }
    $user->save();
  }
  /**
   * {@inheritdoc}
   */
  public function getCreatedTime() {
    return $this->get('created')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setCreatedTime($timestamp) {
    $this->set('created', $timestamp);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwner() {
    return $this->get('cuid')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwnerId() {
    return $this->get('cuid')->target_id;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwnerId($uid) {
    $this->set('cuid', $uid);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwner(UserInterface $account) {
    $this->set('cuid', $account->id());
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);
    
    $fields['uid'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Relevant User'))
      ->setDescription(t('The user ID of permission for.'))
      ->setRevisionable(FALSE)
      ->setSetting('target_type', 'user')
      ->setSetting('handler', 'default')
      ->setDefaultValueCallback('Drupal\node\Entity\Node::getCurrentUserId')
      ->setTranslatable(FALSE)
      ->setDisplayOptions('view', array(
        'label' => 'above',
        'type' => 'author',
        'weight' => 0,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'entity_reference_autocomplete',
        'weight' => 1,
        'settings' => array(
          'match_operator' => 'CONTAINS',
          'size' => '60',
          'autocomplete_type' => 'tags',
          'placeholder' => '',
        ),
      ))
      ->setDisplayConfigurable('form', FALSE)
      ->setDisplayConfigurable('view', TRUE);
      
    $fields['rid'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Relevant Role'))
      ->setDescription(t('The role ID of permission for.'))
      ->setRevisionable(FALSE)
      ->setSetting('target_type', 'user_role')
      ->setSetting('handler', 'default')
      ->setTranslatable(FALSE)
      ->setDisplayOptions('form', array(
        'type' => 'options_select',
        'weight' => 5,
      ))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);
      
    $fields['tid'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Relevant Term'))
      ->setDescription(t('The term ID of permission for.'))
      ->setRevisionable(FALSE)
      ->setSetting('target_type', 'taxonomy_term')
      ->setSetting('handler', 'default')
      ->setSetting('handler_settings', ['target_bundles' => ['department']])
      ->setTranslatable(FALSE)
      ->setDisplayOptions('form', array(
        'type' => 'options_select',
        'weight' => 2,
        'settings' => array(
          'match_operator' => 'CONTAINS',
          'size' => '60',
          'autocomplete_type' => 'tags',
          'placeholder' => '',
        ),
      ))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);
      
    $fields['vid'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Relevant Vocabulary'))
      ->setDescription(t('The Vocabulary ID of permission for.'))
      ->setRevisionable(FALSE)
      ->setSetting('target_type', 'taxonomy_vocabulary')
      ->setSetting('handler', 'default')
      ->setTranslatable(FALSE);


    $fields['cuid'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Authored by'))
      ->setDescription(t('The user ID of author of the permission entity.'))
      ->setRevisionable(FALSE)
      ->setSetting('target_type', 'user')
      ->setSetting('handler', 'default')
      ->setDefaultValueCallback('Drupal\node\Entity\Node::getCurrentUserId')
      ->setTranslatable(FALSE);
    
    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the entity was created.'));

    return $fields;
  }
}
