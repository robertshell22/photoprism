<?php

namespace Drupal\photoprism_views\Plugin\views\field;

use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;

/**
 * Class Images
 *
 * @ViewsField("photoprism_thumb")
 */
class Thumb extends FieldPluginBase {

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    $raw_images = $this->getValue($values);
    if ($raw_images) {
      foreach($raw_images as $raw_image) {
        $images[] = [
          '#theme' => 'photoprism_thumb',
          '#raw_image' => $raw_image,
          '#image' => $raw_image['image150px'],
        ];
      }
      return [
        '#theme' => 'item_list',
        '#items' => $images,
      ];
    }
  }
}
