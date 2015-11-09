<?php

/**
 * @file
 * Contains \Drupal\restful_search_api\Plugin\resource\Field\ResourceFieldSearchKey.
 */

namespace Drupal\restful_search_api\Plugin\resource\Field;

use Drupal\restful\Http\RequestInterface;
use Drupal\restful\Plugin\resource\DataInterpreter\DataInterpreterInterface;
use Drupal\restful\Plugin\resource\Field\ResourceField;

class ResourceFieldSearchKey extends ResourceField implements ResourceFieldSearchKeyInterface {

  /**
   * Separator to drill down on nested result objects for 'property'.
   */
  const NESTING_SEPARATOR = '::';

  /**
   * Overrides ResourceField::value().
   */
  public function value(DataInterpreterInterface $interpreter) {
    $value = parent::value($interpreter);
    $definition = $this->getDefinition();
    if (!empty($definition['sub-property'])) {
      $parts = explode(static::NESTING_SEPARATOR, $definition['sub-property']);
      foreach ($parts as $part) {
        $value = $value[$part];
      }
    }
    return $value;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(array $field, RequestInterface $request = NULL) {
    $resource_field = new static($field, $request ?: restful()->getRequest());
    $resource_field->addDefaults();
    return $resource_field;
  }

}
