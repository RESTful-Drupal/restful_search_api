<?php

/**
 * @file
 * Contains Drupal\restful_search_api_test\Plugin\resource\search\v1_0\Search.
 */

namespace Drupal\restful_search_api_test\Plugin\resource\search\v1_0;

use Drupal\restful\Plugin\resource\ResourceInterface;
use Drupal\restful_search_api\Plugin\Resource\ResourceSearchBase;

/**
 * Class Search
 * @package Drupal\restful_search_api_test\Plugin\resource\search\v1_0
 *
 * @Resource(
 *   name = "search:1.0",
 *   resource = "search",
 *   label = "Search",
 *   description = "Provides info doing Search API searches.",
 *   dataProvider = {
 *     "searchIndex": "test_index"
 *   },
 *   authenticationOptional = TRUE,
 *   majorVersion = 1,
 *   minorVersion = 0
 * )
 */
class Search extends ResourceSearchBase implements ResourceInterface {

  /**
   * Overrides Resource::publicFields().
   */
  protected function publicFields() {
    return array(
      'entity_id' => array(
        'property' => 'search_api_id',
        'process_callbacks' => array(
          'intVal',
        ),
      ),
      'relevance' => array(
        'property' => 'search_api_relevance',
      ),
      'body' => array(
        'property' => 'body',
      ),
      'title' => array(
        'property' => 'title',
      ),
    );
  }

}
