<?php

namespace Drupal\photoprism_views;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * PhotoPrism base table endpoint plugin manager class. Each PhotoPrism endpoint has a
 * one-to-one mapping with a views base table. Each of which can have a base
 * table endpoint plugin associated with it, which is an object that has
 * specific domain knowledge about the PhotoPrism endpoint it interacts with. Each
 * plugin is resposibile for communicating with that endpoint and translating
 * the response's into \Drupal\views\ResultRow objects.
 */
class PhotoPrismBaseTableEndpointPluginManager extends DefaultPluginManager {

  /**
   * PhotoPrismBaseTableEndpointPluginManager constructor.
   *
   * @param \Traversable $namespaces
   * @param CacheBackendInterface $cache_backend
   * @param ModuleHandlerInterface $module_handler
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct('Plugin/PhotoPrismBaseTableEndpoint', $namespaces, $module_handler, 'Drupal\photoprism_views\PhotoPrismBaseTableEndpointInterface', 'Drupal\photoprism_views\Annotation\PhotoPrismBaseTableEndpoint');

    $this->alterInfo('photoprism_base_table_endpoints_info');
    $this->setCacheBackend($cache_backend, 'photoprism_base_table_endpoints');
  }
}
