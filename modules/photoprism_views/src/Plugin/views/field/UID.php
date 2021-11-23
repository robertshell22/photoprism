<?php

namespace Drupal\photoprism_views_example\Plugin\views\field;

use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;

/**
 * Default implementation of the base field plugin.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("photoprism_uid")
 */
class UID extends FieldPluginBase {

  /**
   * Provide extra data to the administration form.
   */
  public function adminSummary() {
    return $this->label();
  }

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    $value = $this->getValue($values);
    return $this->sanitizeValue($value);
  }
}

