<?php

namespace Drupal\photoprism;

use Drupal\Core\Database\Connection;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use League\OAuth2\Client\Token\AccessToken;

/**
 * CRUD operations for the photoprism_user_access_tokens table.
 */
class PhotoPrismSessionIdManager {

  const TOKEN_TABLE = 'photoprism_user_session_ids';

  /**
   * Database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * PhotoPrism client.
   *
   * @var \Drupal\photoprism\PhotoPrismService
   */
  protected $PhotoPrismService;

  /**
   * @var int
   */
  protected $expiration;

  /**
   * @var int
   */
  private static $timeNow;

  /**
   * @return int
   */
  public function getTimeNow()
  {
    return self::$timeNow ? self::$timeNow : time();
  }

  /**
   * PhotoPrismSessionIdManager constructor.
   *
   * @param Connection $connection
   * @param PhotoPrismService $photoprism_service
   */
  public function __construct(Connection $connection, PhotoPrismService $photoprism_service) {
    $this->connection = $connection;
    $this->PhotoPrismService = $photoprism_service;
  }

  /**
   * Load an access token by uid.
   *
   * @param int $uid
   *   User id for which to look up an access token.
   * @return array|null
   *   Returns an associative array of the access token details for the given
   *   uid if they exist, otherwise NULL.
   */
  public function load($uid) {
    $raw_tokens = $this->loadMultiple([$uid]);
    return isset($raw_tokens[$uid]) ? $raw_tokens[$uid] : NULL;
  }

  /**
   * Loads one or more access tokens.
   *
   * @param array|NULL $uids
   *  An array of uids, or NULL to load all access tokens.
   */
  public function loadMultiple($uids = NULL) {
    $query = $this->connection->select(self::TOKEN_TABLE, 'f')
      ->fields('f');
    if (!empty($uids)) {
      $query->condition('uid', $uids, 'IN');
    }
    return $query->execute()
      ->fetchAllAssoc('uid', \PDO::FETCH_ASSOC);
  }

  /**
   * @inheritdoc
   */
  public function hasExpired()
  {
    $raw_tokens = $this->loadMultiple($uids = NULL);
    $expiration = $raw_tokens['expiration'];

    if (empty($expiration)) {
      throw new RuntimeException('"expiration" is not set on the token');
    }

    return $expiration < time();
  }

  /**
   * Save access token details for the given uid.
   *
   * @param int $uid
   *   User id for which to save access token details.
   * @param array $data
   *   Associative array of access token details.
   */
  public function save($uid, $data) {
    $this->connection->merge(self::TOKEN_TABLE)
      ->key(['uid' => $uid])
      ->fields($data)
      ->execute();
  }

  /**
   * Delete access token details for the given uid.
   *
   * @param int $uid
   *   User id for which to delete access token details.
   */
  public function delete($uid) {
    $this->connection->delete(self::TOKEN_TABLE)
      ->condition('uid', $uid)
      ->execute();
  }
}
