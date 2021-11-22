<?php

namespace Drupal\photoprism_views\Plugin\PhotoPrismBaseTableEndpoint;

use Drupal\photoprism_views\PhotoPrismBaseTableEndpointBase;

/**
 * PhotoPrism Activity Time Series endpoint.
 *
 * @PhotoPrismBaseTableEndpoint(
 *   id = "albums",
 *   name = @Translation("PhotoPrism albums"),
 *   description = @Translation("Retrieves albums.")
 * )
 */
class Albums extends PhotoPrismBaseTableEndpointBase {

  /**
   * {@inheritdoc}
   */
  public function getRowBySessionId(SessionId $sessionId, $arguments = NULL) {
    if ($data = $this->PhotoPrismService->getAlbums()) {
      $data = $data->toArray();
      $data = $this->filterArrayByPath($data, array_keys($this->getFields()));

      $config_factory = \Drupal::service('config.factory');
      $config = $config_factory->get('photoprism.application_settings');

      $api_url = $config['server_url'];

      // Adjust avatar and avatar150
      $data['Thumb'] = $api_url.'/t/'.$data['Thumb'].'/shellfam/tile_500';

      // Change memberSince to timestamp
      $data['memberSince'] = strtotime($data['memberSince']);

      return $data;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getFields() {
    $integer = ['id' => 'numeric'];
    $float = [
      'id' => 'numeric',
      'float' => TRUE,
    ];
    $standard = [
      'id' => 'standard',
    ];
    return [
      'UID' => [
        'title' => $this->t('Album ID'),
        'field' => $standard,
      ],
      'Title' => [
        'title' => $this->t('Album Title'),
        'field' => $standard,
      ],
      'PhotoCount' => [
        'title' => $this->t('Photo Count'),
        'field' => $integer,
      ],
      'Thumb' => [
        'title' => $this->t('Thumbnail'),
        'field' => [
          'id' =>'photoprism_thumb',
        ],
      ],
    ];
  }
}
