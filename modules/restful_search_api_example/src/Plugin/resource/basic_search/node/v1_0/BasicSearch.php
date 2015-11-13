<?php

/**
 * @file
 * Contains \Drupal\restful_search_api_example\Plugin\resource\basic_search\node\v1_0\BasicSearch.
 */

namespace Drupal\restful_search_api_example\Plugin\resource\basic_search\node\v1_0;
use Drupal\restful\Plugin\resource\ResourceInterface;
use Drupal\restful_search_api\Plugin\Resource\ResourceSearchBase;

/**
 * Class BasicSearch
 * @package Drupal\restful_search_api_example\Plugin\resource\basic_search\node\v1_0
 *
 * @Resource(
 *   name = "basic_search:1.0",
 *   resource = "basic_search",
 *   label = "Basic Search",
 *   description = "Provides basic info doing Search API searches.",
 *   dataProvider = {
 *     "searchIndex": "default_node_index",
 *     "idField": "entity_id"
 *   },
 *   renderCache = {
 *     "render": FALSE,
 *   },
 *   authenticationTypes = TRUE,
 *   authenticationOptional = TRUE,
 *   majorVersion = 1,
 *   minorVersion = 0,
 *   formatter = "json"
 * )
 */
class BasicSearch extends ResourceSearchBase implements ResourceInterface {

  /**
   * Overrides Resource::publicFields().
   */
  public function publicFields() {
    return array(
      'entity_id' => array(
        'property' => 'search_api_id',
        'process_callbacks' => array(
          'intVal',
        ),
      ),
      'version_id' => array(
        'property' => 'vid',
        'process_callbacks' => array(
          'intVal',
        ),
      ),
      'relevance' => array(
        'property' => 'search_api_relevance',
      ),
      'body' => array(
        'property' => 'body',
        'sub_property' => LANGUAGE_NONE . '::0::value',
      ),
      'title' => array(
        'property' => 'title',
      ),
    );
  }

}
