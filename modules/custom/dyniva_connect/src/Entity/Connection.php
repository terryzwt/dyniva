<?php

namespace Drupal\dyniva_connect\Entity;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\dyniva_connect\ConnectionInterface;
use Drupal\user\UserInterface;

/**
 * Defines the Connection entity.
 *
 * @ingroup dyniva_connect
 *
 * @ContentEntityType(
 *   id = "connection",
 *   label = @Translation("Connection"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\dyniva_connect\ConnectionListBuilder",
 *     "views_data" = "Drupal\dyniva_connect\Entity\ConnectionViewsData",
 *
 *     "form" = {
 *       "default" = "Drupal\dyniva_connect\Form\ConnectionForm",
 *       "add" = "Drupal\dyniva_connect\Form\ConnectionForm",
 *       "edit" = "Drupal\dyniva_connect\Form\ConnectionForm",
 *       "delete" = "Drupal\dyniva_connect\Form\ConnectionDeleteForm",
 *     },
 *     "access" = "Drupal\dyniva_connect\ConnectionAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\dyniva_connect\ConnectionHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "dyniva_connection",
 *   admin_permission = "administer connection entities",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "name",
 *     "uuid" = "uuid",
 *     "uid" = "user_id",
 *     "langcode" = "langcode",
 *     "status" = "status",
 *   },
 *   links = {
 *     "canonical" = "/manage/connection/{connection}",
 *     "add-form" = "/admin/structure/connect/connection/add",
 *     "edit-form" = "/admin/structure/connect/connection/{connection}/edit",
 *     "delete-form" = "/admin/structure/connect/connection/{connection}/delete",
 *     "collection" = "/admin/structure/connect/connection",
 *   },
 *   field_ui_base_route = "connection.settings"
 * )
 */
class Connection extends ContentEntityBase implements ConnectionInterface {
  use EntityChangedTrait;
  /**
   * {@inheritdoc}
   */
  public static function preCreate(EntityStorageInterface $storage_controller, array &$values) {
    parent::preCreate($storage_controller, $values);
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
  public function getOpenid() {
    return $this->get('openid')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setOpenid($openid) {
    $this->set('openid', $openid);
    return $this;
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
  public function getConnector() {
    return $this->get('connector_id')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function getConnectorId() {
    return $this->get('connector_id')->target_id;
  }

  /**
   * {@inheritdoc}
   */
  public function setConnectorId($id) {
    $this->set('connector_id', $id);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setConnector(Connector $connector) {
    $this->set('connector_id', $connector->id());
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function isPublished() {
    return (bool) $this->getEntityKey('status');
  }

  /**
   * {@inheritdoc}
   */
  public function setPublished($published) {
    $this->set('status', $published ? NODE_PUBLISHED : NODE_NOT_PUBLISHED);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields['id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('ID'))
      ->setDescription(t('The ID of the Connection entity.'))
      ->setReadOnly(TRUE);
    $fields['uuid'] = BaseFieldDefinition::create('uuid')
      ->setLabel(t('UUID'))
      ->setDescription(t('The UUID of the Connection entity.'))
      ->setReadOnly(TRUE);

    $fields['user_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Connect user'))
      ->setDescription(t('The user ID of connect of the Connection entity.'))
      ->setSetting('target_type', 'user')
      ->setSetting('handler', 'default')
      ->setDisplayOptions('view', array(
        'label' => 'hidden',
        'type' => 'author',
        'weight' => 0,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'entity_reference_autocomplete',
        'weight' => 5,
        'settings' => array(
          'match_operator' => 'CONTAINS',
          'size' => '60',
          'autocomplete_type' => 'tags',
          'placeholder' => '',
        ),
      ))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);
      
    $fields['connector_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Connector'))
      ->setDescription(t('The ID of connector.'))
      ->setSetting('target_type', 'connector')
      ->setSetting('handler', 'default')
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
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Name'))
      ->setDescription(t('The name of the Connection entity.'))
      ->setSettings(array(
        'max_length' => 50,
        'text_processing' => 0,
      ))
      ->setDefaultValue('')
      ->setDisplayOptions('view', array(
        'label' => 'above',
        'type' => 'string',
        'weight' => 2,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'string_textfield',
        'weight' => 2,
      ))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);
      
    $fields['openid'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Openid'))
      ->setDescription(t('The openid of the Connection entity.'))
      ->setSettings(array(
        'max_length' => 255,
        'text_processing' => 0,
      ))
      ->setDefaultValue('')
      ->setDisplayOptions('view', array(
        'label' => 'above',
        'type' => 'string',
        'weight' => 3,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'string_textfield',
        'weight' => 3,
      ))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);
      
    $fields['subscribe'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Subscribe'))
      ->setDescription(t('The subscribe status of the Connection entity.'))
      ->setDefaultValue(0)
      ->setDisplayOptions('form', array(
        'type' => 'options_buttons',
        'weight' => 4,
      ))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);
      
    $fields['nickname'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Nickname'))
      ->setDescription(t('The nickname of the Connection entity.'))
      ->setSettings(array(
        'max_length' => 255,
        'text_processing' => 0,
      ))
      ->setDefaultValue('')
      ->setDisplayOptions('view', array(
        'label' => 'above',
        'type' => 'string',
        'weight' => 5,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'string_textfield',
        'weight' => 5,
      ))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);
      
    $fields['sex'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Sex'))
      ->setDescription(t('The sex of the Connection entity.'))
      ->setSettings(array(
        'max_length' => 255,
        'text_processing' => 0,
      ))
      ->setDefaultValue('')
      ->setDisplayOptions('view', array(
        'label' => 'above',
        'type' => 'string',
        'weight' => 6,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'string_textfield',
        'weight' => 6,
      ))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);
      
    $fields['language'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Language'))
      ->setDescription(t('The language of the Connection entity.'))
      ->setSettings(array(
        'max_length' => 255,
        'text_processing' => 0,
      ))
      ->setDefaultValue('')
      ->setDisplayOptions('view', array(
        'label' => 'above',
        'type' => 'string',
        'weight' => 7,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'string_textfield',
        'weight' => 7,
      ))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);
      
    $fields['city'] = BaseFieldDefinition::create('string')
      ->setLabel(t('City'))
      ->setDescription(t('The city of the Connection entity.'))
      ->setSettings(array(
        'max_length' => 255,
        'text_processing' => 0,
      ))
      ->setDefaultValue('')
      ->setDisplayOptions('view', array(
        'label' => 'above',
        'type' => 'string',
        'weight' => 8,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'string_textfield',
        'weight' => 8,
      ))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);
      
    $fields['province'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Province'))
      ->setDescription(t('The province of the Connection entity.'))
      ->setSettings(array(
        'max_length' => 255,
        'text_processing' => 0,
      ))
      ->setDefaultValue('')
      ->setDisplayOptions('view', array(
        'label' => 'above',
        'type' => 'string',
        'weight' => 9,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'string_textfield',
        'weight' => 9,
      ))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);
      
    $fields['country'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Country'))
      ->setDescription(t('The country of the Connection entity.'))
      ->setSettings(array(
        'max_length' => 255,
        'text_processing' => 0,
      ))
      ->setDefaultValue('')
      ->setDisplayOptions('view', array(
        'label' => 'above',
        'type' => 'string',
        'weight' => 10,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'string_textfield',
        'weight' => 10,
      ))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);
      
    $fields['headimgurl'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Headimgurl'))
      ->setDescription(t('The headimgurl of the Connection entity.'))
      ->setSettings(array(
        'max_length' => 255,
        'text_processing' => 0,
      ))
      ->setDefaultValue('')
      ->setDisplayOptions('view', array(
        'label' => 'above',
        'type' => 'string',
        'weight' => 11,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'string_textfield',
        'weight' => 11,
      ))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);
      
    $fields['unionid'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Unionid'))
      ->setDescription(t('The unionid of the Connection entity.'))
      ->setSettings(array(
        'max_length' => 255,
        'text_processing' => 0,
      ))
      ->setDefaultValue('')
      ->setDisplayOptions('view', array(
        'label' => 'above',
        'type' => 'string',
        'weight' => 12,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'string_textfield',
        'weight' => 12,
      ))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);
      
    $fields['remark'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Remark'))
      ->setDescription(t('The remark of the Connection entity.'))
      ->setSettings(array(
        'max_length' => 255,
        'text_processing' => 0,
      ))
      ->setDefaultValue('')
      ->setDisplayOptions('view', array(
        'label' => 'above',
        'type' => 'string',
        'weight' => 13,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'string_textfield',
        'weight' => 13,
      ))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);
      
    $fields['groupid'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Groupid'))
      ->setDescription(t('The groupid of the Connection entity.'))
      ->setSettings(array(
        'max_length' => 255,
        'text_processing' => 0,
      ))
      ->setDefaultValue('')
      ->setDisplayOptions('view', array(
        'label' => 'above',
        'type' => 'string',
        'weight' => 14,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'string_textfield',
        'weight' => 14,
      ))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);
      
    $fields['lng'] = BaseFieldDefinition::create('string')
      ->setLabel(t('longitude'))
      ->setDescription(t('The longitude of the Connection entity.'))
      ->setSettings(array(
        'max_length' => 255,
        'text_processing' => 0,
      ))
      ->setDefaultValue('')
      ->setDisplayOptions('view', array(
        'label' => 'above',
        'type' => 'string',
        'weight' => 15,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'string_textfield',
        'weight' => 15,
      ))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);
      
    $fields['lat'] = BaseFieldDefinition::create('string')
      ->setLabel(t('latitude'))
      ->setDescription(t('The latitude of the Connection entity.'))
      ->setSettings(array(
        'max_length' => 255,
        'text_processing' => 0,
      ))
      ->setDefaultValue('')
      ->setDisplayOptions('view', array(
        'label' => 'above',
        'type' => 'string',
        'weight' => 16,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'string_textfield',
        'weight' => 16,
      ))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);
      
    $fields['zoom'] = BaseFieldDefinition::create('string')
      ->setLabel(t('zoom'))
      ->setDescription(t('The zoom of the Connection entity.'))
      ->setSettings(array(
        'max_length' => 255,
        'text_processing' => 0,
      ))
      ->setDefaultValue('')
      ->setDisplayOptions('view', array(
        'label' => 'above',
        'type' => 'string',
        'weight' => 17,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'string_textfield',
        'weight' => 17,
      ))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);
      
    $fields['status'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Publishing status'))
      ->setDescription(t('A boolean indicating whether the Connection is published.'))
      ->setDefaultValue(TRUE);

    $fields['langcode'] = BaseFieldDefinition::create('language')
      ->setLabel(t('Language code'))
      ->setDescription(t('The language code for the Connection entity.'))
      ->setDisplayOptions('form', array(
        'type' => 'language_select',
        'weight' => 20,
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
