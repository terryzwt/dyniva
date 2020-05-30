<?php

namespace Drupal\dyniva_connect\Entity;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\dyniva_connect\ConnectorInterface;
use Drupal\user\UserInterface;
use Drupal\Component\Serialization\Json;

/**
 * Defines the Connector entity.
 *
 * @ingroup dyniva_connect
 *
 * @ContentEntityType(
 *   id = "connector",
 *   label = @Translation("Connector"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\dyniva_connect\ConnectorListBuilder",
 *     "views_data" = "Drupal\dyniva_connect\Entity\ConnectorViewsData",
 *
 *     "form" = {
 *       "default" = "Drupal\dyniva_connect\Form\ConnectorForm",
 *       "add" = "Drupal\dyniva_connect\Form\ConnectorForm",
 *       "edit" = "Drupal\dyniva_connect\Form\ConnectorForm",
 *       "delete" = "Drupal\dyniva_connect\Form\ConnectorDeleteForm",
 *     },
 *     "access" = "Drupal\dyniva_connect\ConnectorAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\dyniva_connect\ConnectorHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "dyniva_connector",
 *   admin_permission = "administer connector entities",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "name",
 *     "uuid" = "uuid",
 *     "uid" = "user_id",
 *     "langcode" = "langcode",
 *     "status" = "status",
 *   },
 *   links = {
 *     "canonical" = "/manage/connector/{connector}",
 *     "add-form" = "/admin/structure/connect/connector/add",
 *     "edit-form" = "/admin/structure/connect/connector/{connector}/edit",
 *     "delete-form" = "/admin/structure/connect/connector/{connector}/delete",
 *     "collection" = "/admin/structure/connect/connector",
 *   },
 *   field_ui_base_route = "connector.settings"
 * )
 */
class Connector extends ContentEntityBase implements ConnectorInterface {
  use EntityChangedTrait;
  /**
   * {@inheritdoc}
   */
  public static function preCreate(EntityStorageInterface $storage_controller, array &$values) {
    parent::preCreate($storage_controller, $values);
    $values += array(
      'user_id' => \Drupal::currentUser()->id(),
    );
  }
  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage) {
    if(is_array($this->getConfigData())){
      $this->setConfigData($this->getConfigData());
    }
    parent::preSave($storage);
  }
  
  /**
   * {@inheritdoc}
   */
  public function getConfigData() {
    $value = $this->get('config_data')->value;
    return Json::decode($value);
  }

  /**
   * {@inheritdoc}
   */
  public function setConfigData($config) {
    if(is_array($config)){
      $config = Json::encode($config);
    }
    $this->set('config_data', $config);
    return $this;
  }
  
  /**
   * {@inheritdoc}
   */
  public function getName() {
    return $this->get('name')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setName($name) {
    $this->set('name', $name);
    return $this;
  }
  
  /**
   * {@inheritdoc}
   */
  public function getType(){
    return $this->get('type')->value;
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
    return $this->get('user_id')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwnerId() {
    return $this->get('user_id')->target_id;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwnerId($uid) {
    $this->set('user_id', $uid);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwner(UserInterface $account) {
    $this->set('user_id', $account->id());
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function isPublished() {
    return (bool) $this->getEntityKey('status');
  }
  
  public function isDefault() {
    return (bool) $this->get('isdefault');
  }

  /**
   * {@inheritdoc}
   */
  public function setPublished($published) {
    $this->set('status', $published ? NODE_PUBLISHED : NODE_NOT_PUBLISHED);
    return $this;
  }

  public function getConnection($values){
    if(empty($values['openid'])){
      return false;
    }
    $query = \Drupal::entityQuery('connection')
    ->condition('openid',$values['openid'])
    ->condition('connector_id',$this->id());
    
    $ids = $query->execute();
    $id = reset($ids);
    if($id){
      $connection = entity_load('connection', $id);
      if(!empty($values['nickname'])){
        $connection->nickname->value = $values['nickname'];
      }
      if(!empty($values['sex'])){
        $connection->sex->value = $values['sex'];
      }
      if(!empty($values['language'])){
        $connection->language->value = $values['language'];
      }
      if(!empty($values['city'])){
        $connection->city->value = $values['city'];
      }
      if(!empty($values['province'])){
        $connection->province->value = $values['province'];
      }
      if(!empty($values['country'])){
        $connection->country->value = $values['country'];
      }
      if(!empty($values['headimgurl'])){
        $connection->headimgurl->value = $values['headimgurl'];
      }
      if(!empty($values['remark'])){
        $connection->remark->value = $values['remark'];
      }
      if(!empty($values['unionid'])){
        $connection->unionid->value = $values['unionid'];
      }
      if(!empty($values['groupid'])){
        $connection->groupid->value = $values['groupid'];
      }
      $connection->save();
    }else{
      if(empty($values['name']) && !empty($values['nickname'])){
        $values['name'] = $values['nickname'];
      }
      if(empty($values['name'])){
        $values['name'] = $values['openid'];
      }
      
      $values['name'] = $this->getName() . '-' . $values['name'];
      $connection = entity_create('connection',$values);
      $connection->setOwnerId(0);
      $connection->setConnector($this);
      $connection->save();
    }
    return $connection;
  }
  
  public function getConnectionByUserId($uid){
    
    $connection = false;
    
    $query = \Drupal::entityQuery('connection')
    ->condition('user_id',$uid)
    ->condition('connector_id',$this->id());
    
    $ids = $query->execute();
    $id = reset($ids);
    if($id){
      $connection = entity_load('connection', $id);
    }
    return $connection;
  }
  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields['id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('ID'))
      ->setDescription(t('The ID of the Connector entity.'))
      ->setReadOnly(TRUE);
    $fields['uuid'] = BaseFieldDefinition::create('uuid')
      ->setLabel(t('UUID'))
      ->setDescription(t('The UUID of the Connector entity.'))
      ->setReadOnly(TRUE);

    $fields['user_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Authored by'))
      ->setDescription(t('The user ID of author of the Connector entity.'))
      ->setRevisionable(TRUE)
      ->setSetting('target_type', 'user')
      ->setSetting('handler', 'default')
      ->setDefaultValueCallback('Drupal\node\Entity\Node::getCurrentUserId')
      ->setTranslatable(TRUE)
      ->setDisplayOptions('view', array(
        'label' => 'hidden',
        'type' => 'author',
        'weight' => 0,
      ))
      ->setDisplayConfigurable('view', TRUE);

    $fields['name'] = BaseFieldDefinition::create('string')
      ->setRequired(true)
      ->setLabel(t('Name'))
      ->setDescription(t('The name of the Connector entity.'))
      ->setSettings(array(
        'max_length' => 50,
        'text_processing' => 0,
      ))
      ->setDefaultValue('')
      ->setDisplayOptions('view', array(
        'label' => 'above',
        'type' => 'string',
        'weight' => -4,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'string_textfield',
        'weight' => -4,
      ))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['isdefault'] = BaseFieldDefinition::create('boolean')
      ->setRequired(true)
      ->setLabel(t('Default connector'))
      ->setDescription(t('Is default connector.'))
      ->setDefaultValue(0)
      ->setDisplayOptions('form', array(
        'type' => 'options_buttons',
        'weight' => 4,
      ))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);
    
    $fields['type'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Connector type'))
      ->setDescription(t('The connector type plugin id.'));
      
    $fields['config_data'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Connector config data'))
      ->setDescription(t('Connector config data.'))
      ->setDefaultValue("[]");
    
    $fields['status'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Publishing status'))
      ->setDescription(t('A boolean indicating whether the Connector is published.'))
      ->setDefaultValue(TRUE);

    $fields['langcode'] = BaseFieldDefinition::create('language')
      ->setLabel(t('Language code'))
      ->setDescription(t('The language code for the Connector entity.'))
      ->setDisplayOptions('form', array(
        'type' => 'language_select',
        'weight' => 10,
      ))
      ->setDisplayConfigurable('form', TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the entity was created.'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the entity was last edited.'));

    return $fields;
  }

}
