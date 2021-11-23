<?php

namespace Drupal\photoprism;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use GuzzleHttp\ClientInterface;
use Drupal\Core\Database\Connection;
use GuzzleHttp\Exception\RequestException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Class PhotoPrismService.
 */
class PhotoPrismService {

  /**
   * Drupal\Core\Entity\EntityTypeManagerInterface definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Config object.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $config;

  /**
   * Http client.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected $httpClient;

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * Constructs a new CpdActivenetService object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   A Guzzle client object.
   * @param \GuzzleHttp\ClientInterface $httpClient
   *   A Guzzle client object.
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, ConfigFactoryInterface $config_factory, ClientInterface $httpClient, Connection $database) {

    $this->entityTypeManager = $entity_type_manager;
    $this->config = $config_factory->get('photoprism.application_settings');
    $this->httpClient = $httpClient;
    $this->database = $database;
  }

  /**
   * {@inheritdoc}
   */
  public function getSessionId() {
    try {
      $url = $this->config['server_url'] . '/session';
      $options['headers'] = ['Content-Type' => 'application/json'];
      $user = $this->config['username'];
      $pass = $this->config['password'];
      $credentials = ['username' => $user, 'password' => $pass,];
      $options['json'] = $credentials;

      $request = \Drupal::httpClient()->post($url, $options);
      $response = $request->getHeader('X-Session-Id');

      return $response;
  }
    catch (RequestException $e) {
      $this->handleRequestException($e);
      return [];
    }
  }

  /**
   * Calls api to get detailed data for program or event.
   *
   * Checks for failure due to API throttling and attempts to correct.
   *
   * @param string $url
   *   API url string.
   * @param array $options
   *   Request options to apply.
   *
   * @return string
   *   API response object.
   */
  public function getAlbums($session_id) {

    try {
      $url = $this->config['server_url'] . '/albums';

      $options['headers'] = ['X-Session-ID' => $this->getSessionId()];
      $options['query'] = ['count' => '1000'];

      $request = \Drupal::httpClient()->get($url, $options);
      $response = $request->getBody()->getContents();

      return $response;
    }
    catch (RequestException $e) {
      $this->handleRequestException($e);
    }
  }

  /**
   * Calls api to get detailed data for program or event.
   *
   * Checks for failure due to API throttling and attempts to correct.
   *
   * @param string $url
   *   API url string.
   * @param array $options
   *   Request options to apply.
   *
   * @return string
   *   API response object.
   */
  public function getPhotos($url, $options = []) {

    try {
      $url = $this->config['server_url'] . '/photos';

      $options['headers'] = ['X-Session-ID' => $this->getSessionId()];
      $options['query'] = ['count' => '1000'];

      $request = \Drupal::httpClient()->get($url, $options);
      $response = $request->getBody()->getContents();

      return $response;
    }
    catch (RequestException $e) {
      $this->handleRequestException($e);
    }
  }

  /**
   * Calls api to get detailed data for program or event.
   *
   * Checks for failure due to API throttling and attempts to correct.
   *
   * @param string $album_uid
   *   API url string.
   *
   * @return string
   *   API response object.
   */
  public function getPhotosByAlbum($album_uid) {

    try {
      $url = $this->config['server_url'] . '/photos';

      $options['headers'] = ['X-Session-ID' => $this->getSessionId()];
      $options['query'] = ['count' => '1000'];
      $options['query'] = [
        'album' => $album_uid,
      ];

      $request = \Drupal::httpClient()->get($url, $options);
      $response = $request->getBody()->getContents();

      return $response;
    }
    catch (RequestException $e) {
      $this->handleRequestException($e);
    }
  }

  /**
   * Custom exception handler for making requests to external API.
   *
   * @param \GuzzleHttp\Exception\RequestException $e
   *   Exception object.
   */
  protected function handleRequestException(RequestException $e) {
    // Throws error in case of httpClient request failure.
    $this->logger->error($e->getRequest()->getMethod() . ' ' . $e->getRequest()->getUri() . ':<br/>' . $e->getResponse()->getBody());
    throw new NotFoundHttpException();
  }

}
