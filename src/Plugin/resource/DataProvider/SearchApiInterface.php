<?php

/**
 * @file
 * Contains \Drupal\restful_search_api\Plugin\resource\DataProvider\SearchApiInterface.
 */

namespace Drupal\restful_search_api\Plugin\resource\DataProvider;

use Drupal\restful\Plugin\resource\DataProvider\DataProviderInterface;

interface SearchApiInterface extends DataProviderInterface {

  /**
   * Pass additional HATEOAS to the formatter.
   */
  public function additionalHateoas();

}
