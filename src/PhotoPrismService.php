<?php

namespace Drupal\photoprism;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Url;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\ClientInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Component\Serialization\Json;
use Drupal\Core\Http\ClientFactory;
use Drupal\Core\Session\AccountProxy;
use Drupal\Core\Site\Settings;
use GuzzleHttp\Exception\RequestException;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Class PhotoPrismService.
 */
class PhotoPrismService {

  /**
   * Drupal\Core\Entity\EntityTypeManager definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * Config object.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $config;

  /**
   * Http client.
   *
   * @var \GuzzleHttp\Client
   */
  protected $httpClient;

  /**
   * The API Key.
   *
   * @var string
   */
  protected $base_uri;

  /**
   * The API Key.
   *
   * @var string
   */
  protected $headers;

  /**
   * The API Key.
   *
   * @var string
   */
  protected $credentials;

  /**
   * The API Key.
   *
   * @var string
   */
  protected $parameters;

  /**
   * The API Key.
   *
   * @var string
   */
  protected $endpoints;

  /**
   * The API Key.
   *
   * @var string
   */
  protected $session_id;

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
   * @param \GuzzleHttp\ClientInterface $httpClient
   *   A Guzzle client object.
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, ConfigFactoryInterface $config_factory, ClientInterface $httpClient, Connection $database) {
    $this->entityTypeManager = $entity_type_manager;
    $this->config = $config_factory->get('photoprism.application_settings');
    $this->httpClient = $httpClient;
    $base_uri = [
      'base_uri' => $this->config['server_url'],
    ];
    $this->setBaseUri($base_uri);
    $headers = [
      'X-Session-ID' => $this->getSessionId(),
    ];
    $this->setRequestHeaders($headers);

    $credentials = [
      'username' => $this->config['username'],
      'password' => $this->config['password'],
    ];
    $this->setCredentials($credentials);

    $parameters = [
      'count' => '1000',
    ];
    $this->setParameters($parameters);

    $endpoints = [
      'session' => '/session',
      'albums' => '/albums',
      'photos' => '/photos',
    ];
    $this->setEndpoints($endpoints);

    $this->database = $database;
  }

  /**
   * {@inheritdoc}
   */
  public function setBaseUri(array $base_uri) {
    $this->base_uri = $base_uri;
  }

  /**
   * {@inheritdoc}
   */
  public function getBaseUri() {
    return !empty($this->base_uri) ? $this->base_uri : [];
  }

  /**
   * {@inheritdoc}
   */
  public function setRequestHeaders(array $headers) {
    $this->headers = $headers;
  }

  /**
   * {@inheritdoc}
   */
  public function getRequestHeaders() {
    return !empty($this->headers) ? $this->headers : [];
  }

  /**
   * {@inheritdoc}
   */
  public function setCredentials(array $credentials) {
    $this->credentials = $credentials;
  }

  /**
   * {@inheritdoc}
   */
  public function getCredentials() {
    return !empty($this->credentials) ? $this->credentials : [];
  }

  /**
   * {@inheritdoc}
   */
  public function setParameters(array $parameters) {
    $this->parameters = $parameters;
  }

  /**
   * {@inheritdoc}
   */
  public function getParameters() {
    return !empty($this->parameters) ? $this->parameters : [];
  }

  /**
   * {@inheritdoc}
   */
  public function setEndpoints(array $endpoints) {
    $this->endpoints = $endpoints;
  }

  /**
   * {@inheritdoc}
   */
  public function getEndpoints() {
    return !empty($this->endpoints) ? $this->endpoints : [];
  }

  /**
   * {@inheritdoc}
   */
  public function getSessionId() {
    try {
      $endpoints = $this->getEndpoints();
      $session_path = json_decode($endpoints['session'], TRUE);
      $url = $this->getBaseUri() . $session_path;

      $options['headers'] = ['Content-Type' => 'application/json'];
      $options['json'] = $this->getCredentials();

      $request = $this->httpClient->get($url, $options);
      $response = Json::decode($request->getBody());

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
   * @return \Psr\Http\Message\ResponseInterface
   *   API response object.
   */
  public function getAlbums() {

    try {
      $endpoints = $this->getEndpoints();
      $albums_path = json_decode($endpoints['albums'], TRUE);
      $url = $this->getBaseUri() . $albums_path;

      $options['headers'] = ['X-Session-ID' => $this->getSessionId()];
      $options['query'] = $this->getParameters();

      $response = $this->httpClient->request('GET', $url, $options);
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
   * @return \Psr\Http\Message\ResponseInterface
   *   API response object.
   */
  public function getPhotos($url, $options = []) {

    try {
      $endpoints = $this->getEndpoints();
      $photos_path = json_decode($endpoints['photos'], TRUE);
      $url = $this->getBaseUri() . $photos_path;

      $options['headers'] = ['X-Session-ID' => $this->getSessionId()];
      $options['query'] = $this->getParameters();

      $response = $this->httpClient->request('GET', $url, $options);
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
   * @return \Psr\Http\Message\ResponseInterface
   *   API response object.
   */
  public function getPhotosByAlbum($album_uid) {

    try {
      $endpoints = $this->getEndpoints();
      $photos_path = json_decode($endpoints['photos'], TRUE);
      $url = $this->getBaseUri() . $photos_path;

      $options['headers'] = ['X-Session-ID' => $this->getSessionId()];
      $options['query'] = $this->getParameters();
      $options['query'] = [
        'album' => $album_uid,
      ];

      $response = $this->httpClient->request('GET', $url, $options);
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
   * @return \Psr\Http\Message\ResponseInterface
   *   API response object.
   */
  public function makeApiCall($url, $options = []) {

      try {
        $options['headers'] = ['X-Session-ID' => $this->getSessionId()];
        $options['query'] = $this->getParameters();

        $response = $this->httpClient->request('GET', $url, $options);
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
