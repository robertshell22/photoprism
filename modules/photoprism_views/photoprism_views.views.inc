<?php
/**
 * @file
 *
 * Views related hooks.
 */

/**
 * Implements hook_views_data().
 */
function photoprism_views_views_data() {
  $data = [];

  /** @var \Drupal\photoprism_views\PhotoPrismBaseTableEndpointPluginManager $photoprism_base_table_endpoint_manager */
  $photoprism_base_table_endpoint_manager = \Drupal::service('plugin.manager.photoprism_base_table_endpoints');

  $plugin_definitions = $photoprism_base_table_endpoint_manager->getDefinitions();

  foreach ($plugin_definitions as $plugin_id => $plugin_definition) {
    /** @var \Drupal\photoprism_views\PhotoPrismBaseTableEndpointInterface $base_table_endpoint */
    $base_table_endpoint = $photoprism_base_table_endpoint_manager->createInstance($plugin_id);

    $data['photoprism_' . $plugin_id]['table']['group'] = $base_table_endpoint->getName();
    $data['photoprism_' . $plugin_id]['table']['base'] = [
      'title' => $base_table_endpoint->getName(),
      'query_id' => 'photoprism',
      'photoprism_base_table_endpoint_id' => $plugin_id,
      'help' => $base_table_endpoint->getDescription(),
      'defaults' => [
        'field' => $base_table_endpoint->getResponseKey(),
      ],
    ];

    // Tack on all the field definitions.
    foreach ($base_table_endpoint->getFields() as $key => $field) {
      if ($field) {
        $data['photoprism_' . $plugin_id][$key] = $field;
      }
    }

    // Tack on the default uid
    // Filter by Drupal uid
    $data['photoprism_' . $plugin_id]['uid'] = [
      'title' => t('User id'),
      'help' => t('Drupal user id, not to be confused with PhotoPrism profile id.'),
      'field' => [
        'id' => 'standard',
      ],
      'filter' => [
        'id' => 'photoprism_uid',
      ],
    ];

    // Loop over all base table plugins that are not this current one and setup
    // a relationship to them.
    foreach (array_diff(array_keys($plugin_definitions), [$plugin_id]) as $relationship_name) {
      /** @var \Drupal\photoprism_views\PhotoPrismBaseTableEndpointInterface $relationship_endpoint */
      $relationship_endpoint = $photoprism_base_table_endpoint_manager->createInstance($relationship_name);
      $data['photoprism_' . $plugin_id][$relationship_name] = [
        'relationship' => [
          'title' => $relationship_endpoint->getName(),
          'help' => $relationship_endpoint->getDescription(),
          'label' => $relationship_endpoint->getName(),
          'id' => 'photoprism',
          'base' => 'photoprism_' . $relationship_name,
        ],
      ];
    }
  }

  return $data;
}
