<?php

/**
 * @file
 * Contains \RestfulSearchAPISearch.
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
