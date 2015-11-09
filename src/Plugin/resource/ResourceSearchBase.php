<?php

/**
 * @file
 * Contains \Drupal\restful_search_api\Plugin\Resource\ResourceSearchBase.
 */

namespace Drupal\restful_search_api\Plugin\Resource;

use Drupal\restful\Plugin\resource\Resource;
use Drupal\restful\Plugin\resource\ResourceInterface;

abstract class ResourceSearchBase extends Resource implements ResourceInterface {

  /**
   * {@inheritdoc}
   */
  public function additionalHateoas() {
    /* @var \Drupal\restful_search_api\Plugin\resource\DataProvider\SearchApiInterface $provider */
    $provider = $this->getDataProvider();
    return $provider->additionalHateoas();
  }

  /**
   * {@inheritdoc}
   */
  protected function dataProviderClassName() {
    return '\\Drupal\\restful_search_api\\Plugin\\resource\\DataProvider\\SearchApi';
  }

  /**
   * {@inheritdoc}
   *
   * Make sure all fields are ResourceFieldSearchKey by default.
   */
  protected function processPublicFields(array $field_definitions) {
    foreach ($field_definitions as &$field_definition) {
      if (empty($field_definition['class'])) {
        $field_definition['class'] = '\\Drupal\\restful_search_api\\Plugin\\resource\\Field\\ResourceFieldSearchKey';
      }
    }
    $field_definitions = parent::processPublicFields($field_definitions);
    return $field_definitions;
  }


}
