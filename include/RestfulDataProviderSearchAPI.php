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
    // TODO.
  }

  /**
   * {@inheritdoc}
   *
   * @throws \RestfulServerConfigurationException
   *   If the provided search index does not exist.
   */
  public function view($id) {
    // In this case the ID is the search query.
    $options = array();
    $request = $this->getRequest();
    // Construct the options array.

    // limit: The maximum number of search results to return. -1 means no limit.
    $options['limit'] = $this->getRange();

    // offset: The position of the first returned search results relative to the
    // whole result in the index.
    $page = $request['page'] ?: 0;
    $options['offset'] = $options['limit'] * $page;

    // sort: An array of sort directives of the form $field => $order, where
    // $order is either 'ASC' or 'DESC'.
    $options['sort'] = $this->parseRequestForListSort();

    if ($filter = $this->parseRequestForListFilter()) {
      $options['filter'] = $filter;
    }
    try {
      // Query SearchAPI for the results
      $results = search_api_query($this->getSearchIndex(), $options);
    }
    catch (\SearchApiException $e) {
      // Relay the exception with one of RESTful's types.
      throw new \RestfulServerConfigurationException(format_string('Search API Exception: @message', array(
        '@message' => $e->getMessage(),
      )));
    }

    $return = $results;
    return $return;
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
}
