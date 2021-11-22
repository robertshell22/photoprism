<?php

namespace Drupal\photoprism\Controller;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\photoprism\PhotoPrismSessionIdManager;
use Drupal\photoprism\PhotoPrismService;
use Drupal\user\PrivateTempStoreFactory;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\DependencyInjection\ContainerInterface;

class Authorization extends ControllerBase {

  /**
   * PhotoPrism client.
   *
   * @var \Drupal\photoprism\PhotoPrismService
   */
  protected $PhotoPrismService;

  /**
   * PhotoPrism Access Token Manager.
   *
   * @var \Drupal\photoprism\PhotoPrismSessionIdManager
   */
  protected $PhotoPrismSessionIdManager;

  /**
   * Session storage.
   *
   * @var \Drupal\user\PrivateTempStore
   */
  protected $tempStore;

  /**
   * Current request.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $request;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * Authorization constructor.
   *
   * @param PhotoPrismService $photoprism_service
   * @param PhotoPrismSessionIdManager $photoprism_access_token_manager
   * @param PrivateTempStoreFactory $private_temp_store_factory
   * @param Request $request
   * @param AccountInterface $current_user
   */
  public function __construct(PhotoPrismService $photoprism_service, PhotoPrismSessionIdManager $photoprism_access_token_manager, PrivateTempStoreFactory $private_temp_store_factory, Request $request, AccountInterface $current_user) {
    $this->PhotoPrismService = $photoprism_service;
    $this->PhotoPrismSessionIdManager = $photoprism_access_token_manager;
    $this->tempStore = $private_temp_store_factory->get('photoprism');
    $this->request = $request;
    $this->currentUser = $current_user;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('photoprism.client'),
      $container->get('photoprism.access_token_manager'),
      $container->get('user.private_tempstore'),
      $container->get('request_stack')->getCurrentRequest(),
      $container->get('current_user')
    );
  }

  /**
   * Receive the authorization code from a Fitibit Authorization Code Flow
   * redirect, and request an access token from PhotoPrism.
   */
  public function authorize() {

    try {
      // Try to get an access token using the authorization code grant.
      $session_id = $this->PhotoPrismService->getSessionId();

      // Save access token details.
      $this->PhotoPrismSessionIdManager->save($this->currentUser->id(), [
        'session_id' => $session_id->getToken(),
        'expiration' => $session_id->getExpires(),
        'tokens' => $session_id->getRefreshToken(),
        'user_id' => $session_id->getResourceOwnerId(),
      ]);

      drupal_set_message('You\'re PhotoPrism account is now connected.');

      return new RedirectResponse(Url::fromRoute('photoprism_application_settings', ['user' => $this->currentUser->id()])->toString());
    }
    catch (IdentityProviderException $e) {
      watchdog_exception('photoprism', $e);
    }
  }

  /**
   * Check the state key from PhotoPrism to protect against CSRF.
   */
  public function checkAccess() {
    return AccessResult::allowedIf($this->tempStore->get('state') == $this->request->get('state'));
  }
}
