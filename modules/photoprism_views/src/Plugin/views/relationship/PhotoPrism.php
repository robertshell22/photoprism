<?php

namespace Drupal\photoprism_views\Plugin\views\relationship;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\relationship\RelationshipPluginBase;
use Drupal\views\Views;

/**
 * Views relationship plugin for PhotoPrism endpoints.
 *
 * @ViewsRelationship("photoprism")
 */
class PhotoPrism extends RelationshipPluginBase {

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    $form['required']['#access'] = FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    $table_data = Views::viewsData()->get($this->definition['base']);
    $this->query->addRelationship($table_data['table']['base']['photoprism_base_table_endpoint_id']);
  }
}
