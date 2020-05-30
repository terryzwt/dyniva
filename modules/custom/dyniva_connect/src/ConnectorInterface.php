<?php

namespace Drupal\dyniva_connect;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining Connector entities.
 *
 * @ingroup dyniva_connect
 */
interface ConnectorInterface extends ContentEntityInterface, EntityChangedInterface, EntityOwnerInterface {
  // Add get/set methods for your configuration properties here.

  /**
   * Gets the Connector name.
   *
   * @return string
   *   Name of the Connector.
   */
  public function getName();
  
  /**
   * Gets the connector type.
   */
  public function getType();

  /**
   * Sets the Connector name.
   *
   * @param string $name
   *   The Connector name.
   *
   * @return \Drupal\dyniva_connect\ConnectorInterface
   *   The called Connector entity.
   */
  public function setName($name);

  /**
   * Gets the Connector creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Connector.
   */
  public function getCreatedTime();

  /**
   * Sets the Connector creation timestamp.
   *
   * @param int $timestamp
   *   The Connector creation timestamp.
   *
   * @return \Drupal\dyniva_connect\ConnectorInterface
   *   The called Connector entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Returns the Connector published status indicator.
   *
   * Unpublished Connector are only visible to restricted users.
   *
   * @return bool
   *   TRUE if the Connector is published.
   */
  public function isPublished();

  /**
   * Sets the published status of a Connector.
   *
   * @param bool $published
   *   TRUE to set this Connector to published, FALSE to set it to unpublished.
   *
   * @return \Drupal\dyniva_connect\ConnectorInterface
   *   The called Connector entity.
   */
  public function setPublished($published);

}
