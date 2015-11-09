<?php

/**
 * @file
 * Contains \RestfulSearchAPISearch.
 */

/**
 * Class RestfulSearchAPISearch
 * @package Drupal\restful_search_api_test\resource\search\v1_0
 *
 * @Resource(
 *   name = "search:1.0",
 *   resource = "search",
 *   label = "Search",
 *   description = "Provides info doing Search API searches.",
 *   dataProvider = {
 *     "searchIndex": "test_index"
 *   },
 *   majorVersion = 1,
 *   minorVersion = 0
 * )
 */
class RestfulSearchAPISearch extends \RestfulDataProviderSearchAPI implements \RestfulInterface {

  /**
   * Overrides \RestfulBase::publicFieldsInfo().
   */
  public function publicFieldsInfo() {
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
