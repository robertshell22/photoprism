<?php
/**
 * @file
 *
 * Views will look in this file for any views related hooks. Mainly, this is
 * where you put your hook_views_data() implementation.
 */


/**
 * Implements hook_views_data().
 */
function photoprism_views_example_views_data() {
  $data = [];

  // Base data.
  $data['photoprism_profile']['table']['group'] = t('PhotoPrism profile');
  $data['photoprism_profile']['table']['base'] = [
    'title' => t('PhotoPrism profile'),
    'query_id' => 'photoprism',
    'help' => t('PhotoPrism profile data provided by the PhotoPrism API\'s User Profile endpoint.'),
  ];

  // Fields.
  $data['photoprism_profile']['display_name'] = [
    'title' => t('Display name'),
    'help' => t('PhotoPrism users\' display name.'),
    'field' => [
      'id' => 'standard',
    ],
  ];
  $data['photoprism_profile']['average_daily_steps'] = [
    'title' => t('Average daily steps'),
    'help' => t('The average daily steps over all the users logged PhotoPrism data.'),
    'field' => [
      'id' => 'numeric',
    ],
  ];
  $data['photoprism_profile']['avatar'] = [
    'title' => t('Avatar'),
    'help' => t('PhotoPrism users\' account picture.'),
    'field' => [
      'id' => 'photoprism_avatar',
    ],
  ];
  $data['photoprism_profile']['height'] = [
    'title' => t('Height'),
    'help' => t('Fibit users\'s height.'),
    'field' => [
      'id' => 'numeric',
      'float' => TRUE,
    ],
  ];
  $data['photoprism_profile']['uid'] = [
    'title' => t('User id'),
    'help' => t('Drupal user id, not to be confused with PhotoPrism profile id.'),
    'field' => [
      'id' => 'standard',
    ],
    'filter' => [
      'id' => 'photoprism_uid',
    ],
  ];

  return $data;
}