<?php

/**
 * @file
 * Contains \RestfulSearchAPIBasicSearch.
 */

class RestfulSearchAPIBasicSearch extends \RestfulDataProviderSearchAPI implements \RestfulInterface {

  /**
   * Overrides \RestfulBase::publicFieldsInfo().
   */
  public function publicFieldsInfo() {
    return array(
      'entity_id' => array(
        'property' => 'id',
        'process_callbacks' => array(
          'intVal',
        ),
      ),
      'relevancy' => array(
        'property' => 'score',
      ),
    );
  }

}
