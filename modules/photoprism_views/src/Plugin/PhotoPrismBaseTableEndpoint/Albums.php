<?php

namespace Drupal\photoprism_views\Plugin\PhotoPrismBaseTableEndpoint;

use Drupal\photoprism\PhotoPrismService;
use Drupal\photoprism_views\PhotoPrismBaseTableEndpointBase;


/**
 * PhotoPrism Albums endpoint.
 * @param PhotoPrismService $photoprism_service
 *
 * @PhotoPrismBaseTableEndpoint(
 *   id = "albums",
 *   name = @Translation("PhotoPrism albums"),
 *   description = @Translation("Retrieves albums.")
 *   response_key = "UID"
 * )
 */
class Albums extends PhotoPrismBaseTableEndpointBase {

  /**
   * {@inheritdoc}
   */
  public function getRow( $arguments = NULL) {


      if ($data = $this->PhotoPrismService->getAlbums($arguments)) {
        $data = $data->toArray();

        $config_factory = \Drupal::service('config.factory');
        $config = $config_factory->get('photoprism.application_settings');

        $api_url = $config['server_url'];

        // Adjust avatar and avatar150
        $data['Thumb'] = $api_url . '/t/' . $data['Thumb'] . '/shellfam/tile_500';

        return $data;
      }
    }


  /**
   * {@inheritdoc}
   */
  public function getFields() {
    return [
      'UID' => [
        'title' => $this->t('Album ID'),
        'field' => [
          'id' =>'photoprism_uid',
        ],
      ],
      'Title' => [
        'title' => $this->t('Title'),
        'field' => [
          'id' =>'photoprism_title',
        ],
      ],
      'PhotoCount' => [
        'title' => $this->t('Photo Count'),
        'field' => [
          'id' =>'photoprism_count',
        ],
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
