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
  }

  return $data;
}
