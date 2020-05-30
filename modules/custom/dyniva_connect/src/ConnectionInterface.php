<?php

namespace Drupal\dyniva_connect;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining Connection entities.
 *
 * @ingroup dyniva_connect
 */
interface ConnectionInterface extends ContentEntityInterface, EntityChangedInterface, EntityOwnerInterface {
  // Add get/set methods for your configuration properties here.
  /**
   * Gets the Connection name.
   *
   * @return string
   *   Name of the Connection.
   */
  public function getName();

  /**
   * Sets the Connection name.
   *
   * @param string $name
   *   The Connection name.
   *
   * @return \Drupal\dyniva_connect\ConnectionInterface
   *   The called Connection entity.
   */
  public function setName($name);

  /**
   * Gets the Connection creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Connection.
   */
  public function getCreatedTime();

  /**
   * Sets the Connection creation timestamp.
   *
   * @param int $timestamp
   *   The Connection creation timestamp.
   *
   * @return \Drupal\dyniva_connect\ConnectionInterface
   *   The called Connection entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Returns the Connection published status indicator.
   *
   * Unpublished Connection are only visible to restricted users.
   *
   * @return bool
   *   TRUE if the Connection is published.
   */
  public function isPublished();

  /**
   * Sets the published status of a Connection.
   *
   * @param bool $published
   *   TRUE to set this Connection to published, FALSE to set it to unpublished.
   *
   * @return \Drupal\dyniva_connect\ConnectionInterface
   *   The called Connection entity.
   */
  public function setPublished($published);

}
