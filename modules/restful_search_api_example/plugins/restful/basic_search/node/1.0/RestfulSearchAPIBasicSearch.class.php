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
      'id' => array(
        'property' => 'nid',
      ),
      'label' => array(
        'property' => 'title',
      ),
    );
  }

}
