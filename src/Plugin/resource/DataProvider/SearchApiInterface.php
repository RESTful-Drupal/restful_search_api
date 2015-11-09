<?php

/**
 * @file
 * Contains \Drupal\restful_search_api\Plugin\resource\DataProvider\SearchApiInterface.
 */

namespace Drupal\restful_search_api\Plugin\resource\DataProvider;

use Drupal\restful\Plugin\resource\DataProvider\DataProviderInterface;

interface SearchApiInterface extends DataProviderInterface {


  /**
   * Get the total count of entities that match certain request.
   *
   * @return int
   *   The total number of results without including pagination.
   */
  public function getTotalCount();

}
