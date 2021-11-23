<?php

namespace Drupal\photoprism_views;

use Drupal\Component\Plugin\PluginInspectionInterface;

/**
 * Defines an interface for PhotoPrism base table endpoint plugins.
 */
interface PhotoPrismBaseTableEndpointInterface extends PluginInspectionInterface {

  /**
   * Get the name of the plugin.
   *
   * @return string
   *   The name of the plugin.
   */
  public function getName();

  /**
   * Get the description of the plugin.
   *
   * @return string
   *   The description of the plugin.
   */
  public function getDescription();

  /**
   * Get the name of a string key which is always present in the response.
   *
   * @return string
   *   Name of a string key that is always in the response. Keys at depth should
   *   have path parts into the array delimited by colons.
   */
  public function getResponseKey();

  /**
   * Inform views about the fields this endpoint exposes.
   *
   * @return array
   *   Associative array. Keys at depth should have path parts into the array
   *   delimited by colons. Values are an associative array appropriate to pass
   *   along to views in a hook_views_data implementation as the definition of a
   *   field.
   */
  public function getFields();
}
