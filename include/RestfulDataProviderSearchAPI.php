<?php

/**
 * @file
 * Contains \RestfulDataProviderSearchAPI
 */

abstract class RestfulDataProviderSearchAPI extends \RestfulBase implements \RestfulDataProviderSearchAPIInterface {

  /**
   * Index machine name to query against.
   *
   * @var string
   */
  protected $searchIndex;

  /**
   * Total count of results after executing the query.
   *
   * @var int
   */
  protected $totalCount;

  /**
   * Return the search index machine name.
   *
   * @return string
   */
  public function getSearchIndex() {
    return $this->searchIndex;
  }

  /**
   * Set the search index machine name.
   *
   * @param string $searchIndex
   *   The new name.
   */
  public function setSearchIndex($searchIndex) {
    $this->searchIndex = $searchIndex;
  }

  /**
   * Set the total results count after executing the query.
   *
   * @param int $totalCount
   */
  public function setTotalCount($totalCount) {
    $this->totalCount = $totalCount;
  }

  /**
   * Constructs a RestfulDataProviderSearchAPI object.
   *
   * @param array $plugin
   *   Plugin definition.
   * @param RestfulAuthenticationManager $auth_manager
   *   (optional) Injected authentication manager.
   * @param DrupalCacheInterface $cache_controller
   *   (optional) Injected cache backend.
   */
  public function __construct(array $plugin, \RestfulAuthenticationManager $auth_manager = NULL, \DrupalCacheInterface $cache_controller = NULL) {
    parent::__construct($plugin, $auth_manager, $cache_controller);

    // Validate keys exist in the plugin's "data provider options".
    $required_keys = array('search_index');
    $options = $this->processDataProviderOptions($required_keys);

    $this->searchIndex = $options['search_index'];
  }

  /**
   * {@inheritdoc}
   */
  public function getTotalCount() {
    return $this->totalCount;
  }

  /**
   * {@inheritdoc}
   *
   * @throws \RestfulServerConfigurationException
   *   If the provided search index does not exist.
   */
  public function view($id) {
    // In this case the ID is the search query.
    $options = $output = array();
    $request = $this->getRequest();
    // Construct the options array.

    // limit: The maximum number of search results to return. -1 means no limit.
    $options['limit'] = $this->getRange();

    // offset: The position of the first returned search results relative to the
    // whole result in the index.
    $page = empty($request['page']) ? 0 : $request['page'];
    $options['offset'] = $options['limit'] * $page;

    // sort: An array of sort directives of the form $field => $order, where
    // $order is either 'ASC' or 'DESC'.
    if ($sort = $this->queryForListSort()) {
      $options['sort'] = $sort;
    }

    if ($filter = $this->parseRequestForListFilter()) {
      $options['filter'] = $filter;
    }
    try {
      // Query SearchAPI for the results
      $search_results = $this->executeQuery($id, $options);
      foreach ($search_results as $search_result) {
        $output[] = $this->mapSearchResultToPublicFields($search_result);
      }
    }
    catch (\SearchApiException $e) {
      // Relay the exception with one of RESTful's types.
      throw new \RestfulServerConfigurationException(format_string('Search API Exception: @message', array(
        '@message' => $e->getMessage(),
      )));
    }

    return $output;
  }

  /**
   * {@inheritdoc}
   */
  protected function parseRequestForListFilter() {
    // At the moment RESTful only supports AND conjunctions.
    $search_api_filter = new SearchApiQueryFilter('AND');
    $search_api_filters = $search_api_filter->getFilters();
    $filters = parent::parseRequestForListFilter();
    // Now translate the RESTful way into the Search API way.
    foreach ($filters as $filter) {
      $search_api_filters[] = array(
        'field' => $filter['public_field'],
        'value' => $filter['value'],
        'operator' => $filter['operator'],
      );
    }

    return $search_api_filter;
  }

  /**
   * Executes the Search API query and stores the total count.
   *
   * @param string $keywords
   *   Keywords to search.
   * @param array $options
   *   An array of options passed to search_api_query.
   *
   * @return array
   *   The array of results.
   *
   * @see search_api_query()
   */
  protected function executeQuery($keywords, array $options) {
    $resultsObj = search_api_query($this->getSearchIndex(), $options)
      ->keys($keywords)
      ->execute();
    $this->setTotalCount($resultsObj['result count']);
    return array_values($resultsObj['results']);
  }

  /**
   * Sort the query for list.
   *
   * @throws \RestfulBadRequestException
   *
   * @see \RestfulEntityBase::getQueryForList
   */
  protected function queryForListSort() {
    $public_fields = $this->getPublicFields();
    $private_sorts = array();

    // Get the sorting options from the request object.
    $sorts = $this->parseRequestForListSort();

    $sorts = $sorts ? $sorts : $this->defaultSortInfo();

    foreach ($sorts as $sort => $direction) {
      $private_sorts[$public_fields[$sort]['property']] = $direction;
    }

    return $private_sorts;
  }

  /**
   * Return the default sort.
   *
   * @return array
   *   A default sort array.
   */
  public function defaultSortInfo() {
    return array();
  }

  /**
   * Prepares the output array from the search result.
   *
   * @param array $result
   *   Search result from Search API.
   *
   * @return array
   *   The prepared output.
   */
  protected function mapSearchResultToPublicFields($result) {
    $output = array();
    // Loop over all the defined public fields.
    foreach ($this->getPublicFields() as $public_field_name => $info) {
      $value = NULL;
      // If there is a callback defined execute it instead of a direct mapping.
      if ($info['callback']) {
        $value = static::executeCallback($info['callback'], array($result));
      }
      // Map row names to public properties.
      elseif ($info['property']) {
        $value = $result[$info['property']];
      }

      // Execute the process callbacks.
      if ($value && $info['process_callbacks']) {
        foreach ($info['process_callbacks'] as $process_callback) {
          $value = static::executeCallback($process_callback, array($value));
        }
      }

      $output[$public_field_name] = $value;
    }

    return $output;
  }

}
