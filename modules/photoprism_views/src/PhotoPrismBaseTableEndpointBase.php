<?php

namespace Drupal\photoprism_views;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\photoprism\PhotoPrismService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base class for PhotoPrism base table endpoint plugins.
 */
abstract class PhotoPrismBaseTableEndpointBase extends PluginBase implements PhotoPrismBaseTableEndpointInterface, ContainerFactoryPluginInterface  {
  use StringTranslationTrait;

  /**
   * PhotoPrism client.
   *
   * @var \Drupal\photoprism\PhotoPrismService
   */
  protected $PhotoPrismService;

  /**
   * All endpoints will require a PhotoPrismService to do their work, save them all
   * from having to get the serivice from the container.
   *
   * @param array $configuration
   * @param string $plugin_id
   * @param mixed $plugin_definition
   * @param PhotoPrismService $photoprism_service
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, PhotoPrismService $photoprism_service) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->PhotoPrismService = $photoprism_service;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('photoprism.service')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return $this->pluginDefinition['name'];
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->pluginDefinition['description'];
  }

  /**
   * {@inheritdoc}
   */
  public function getResponseKey() {
    return $this->pluginDefinition['response_key'];
  }

}
