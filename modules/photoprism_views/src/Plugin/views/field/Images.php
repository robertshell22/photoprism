<?php

namespace Drupal\photoprism_views\Plugin\views\field;

use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;

/**
 * Class Images
 *
 * @ViewsField("photoprism_images")
 */
class Images extends FieldPluginBase {

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    $raw_images = $this->getValue($values);
    if ($raw_images) {
      foreach($raw_images as $raw_image) {
        $images[] = [
          '#theme' => 'photoprism_image',
          '#raw_image' => $raw_image,
          '#image' => $raw_image['image100px'],
        ];
      }
      return [
        '#theme' => 'item_list',
        '#items' => $images,
      ];
    }
  }
}
