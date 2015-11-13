<?php

/**
 * @file
 * Contains \RestfulDataProviderSearchAPI
 */

abstract class RestfulDataProviderSearchAPI extends \RestfulBase implements \RestfulDataProviderSearchAPIInterface {

  /**
   * Separator to drill down on nested result objects for 'property'.
   */
  const NESTING_SEPARATOR = '::';

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
   * Tracks the sorts that have been applied.
   */
  private $sorted = array();

  /**
   * Additional information for the query.
   */
  protected $hateoas = array();

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
   * Additional HATEOAS to be passed to the formatter.
   */
  public function additionalHateoas() {
    return $this->hateoas;
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

    // This is an emergency sort. Only apply it if no sort could be applied.
    $any_sort_applied = array_filter(array_values($this->sorted));
    $result = reset($output);
    $available_keys = array_keys($result);
    if (!$any_sort_applied) {
      $this->manualArraySort($available_keys, $output);
    }

    return $output;
  }

  /**
   * {@inheritdoc}
   */
  protected function parseRequestForListFilter() {
    // At the moment RESTful only supports AND conjunctions.
    $search_api_filter = new SearchApiQueryFilter('AND');

    $request = $this->getRequest();
    if (empty($request['filter'])) {
      // No filtering is needed.
      return $search_api_filter;
    }
    $url_params = $this->getPluginKey('url_params');
    if (!$url_params['filter']) {
      throw new \RestfulBadRequestException('Filter parameters have been disabled in server configuration.');
    }

    $public_fields = $this->getPublicFields();

    foreach ($request['filter'] as $public_field => $value) {
      $field = empty($public_fields[$public_field]['property']) ? $public_field : $public_fields[$public_field]['property'];

      if (!is_array($value)) {
        // Request uses the shorthand form for filter. For example
        // filter[foo]=bar would be converted to filter[foo][value] = bar.
        $value = array('value' => $value);
      }
      // Set default operator.
      $value += array('operator' => '=');

      // Clean the operator in case it came from the URL.
      // e.g. filter[minor_version][operator]=">="
      $value['operator'] = str_replace(array('"', "'"), '', $value['operator']);

      $this->isValidOperatorsForFilter(array($value['operator']));

      $search_api_filter->condition($field, $value['value'], $value['operator']);
    }

    return $search_api_filter;
  }

  /**
   * Filter the query for list.
   *
   * @param \SearchApiQueryInterface $query
   *   The query object.
   */
  protected function queryForListFilter(\SearchApiQueryInterface $query) {
    $query->filter($this->parseRequestForListFilter());
  }

  /**
   * Executes the Search API query and stores the total count.
   *
   * @param string $keywords
   *   Keywords to search.
   * @param array $options
   *   An array of options passed to search_api_query.
   *
   * @throws \RestfulServerConfigurationException
   *   For invalid indices.
   *
   * @return array
   *   The array of results.
   *
   * @see search_api_query()
   */
  protected function executeQuery($keywords, array $options) {
    $index = search_api_index_load($this->getSearchIndex());

    if (!$index) {
      throw new \RestfulServerConfigurationException(format_string('Search API Exception: Unknown index with ID @id.', array(
        '@id' => $this->getSearchIndex(),
      )));
    }
    $query = $index->query($options);

    $this->queryForListSort($query);
    $this->queryForListFilter($query);
    $resultsObj = $query
      ->keys($keywords)
      ->execute();

    $this->setTotalCount($resultsObj['result count']);
    $results = $index->loadItems(array_keys($resultsObj['results']));

    // Add the index id and the relevance.
    foreach ($resultsObj['results'] as $id => $result) {
      $results[$id]->search_api_id = $result['id'];
      $results[$id]->search_api_relevance = $result['score'];
    }
    if (!empty($resultsObj['search_api_facets'])) {
      $this->hateoas['facets'] = $resultsObj['search_api_facets'];
    }
    $this->hateoas['count'] = $resultsObj['result count'];

    return $results;
  }

  /**
   * Sort the query for list.
   *
   * @param \SearchApiQueryInterface $query
   *   The Search API query.
   *
   * @throws \RestfulBadRequestException
   *
   * @see \RestfulEntityBase::getQueryForList
   */
  protected function queryForListSort(\SearchApiQueryInterface $query) {
    $public_fields = $this->getPublicFields();

    // Get the sorting options from the request object.
    $sorts = $this->parseRequestForListSort();

    $sorts = $sorts ? $sorts : $this->defaultSortInfo();

    foreach ($sorts as $sort => $direction) {
      $property = empty($public_fields[$sort]['property']) ? $sort : $public_fields[$sort]['property'];
      try {
        $query->sort($property, $direction);

        // Mark this sort as applied.
        $this->sorted[$sort] = TRUE;
      }
      catch (\SearchApiException $e) {
        // Do not throw an exception, we will sort manually the array
        // afterwards.
      }
    }
  }

  /**
   * Overrides \RestfulBase::parseRequestForListSort
   */
  protected function parseRequestForListSort() {
    $request = $this->getRequest();
    $public_fields = $this->getPublicFields();

    if (empty($request['sort'])) {
      return array();
    }
    $url_params = $this->getPluginKey('url_params');
    if (!$url_params['sort']) {
      throw new \RestfulBadRequestException('Sort parameters have been disabled in server configuration.');
    }

    $sorts = array();
    foreach (explode(',', $request['sort']) as $sort) {
      $direction = $sort[0] == '-' ? 'DESC' : 'ASC';
      $sort = str_replace('-', '', $sort);

      $sorts[$sort] = $direction;

      // Initially mark all sort criteria as not applied.
      $this->sorted[$sort] = FALSE;
    }
    return $sorts;
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
   * @param object $result
   *   Search result from Search API.
   *
   * @return array
   *   The prepared output.
   */
  protected function mapSearchResultToPublicFields($result) {
    if ($this->getPluginKey('pass_through')) {
      return (array) $result;
    }
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
        $value = $result->{$info['property']};
        if (!empty($info['sub_property'])) {
          $parts = explode(static::NESTING_SEPARATOR, $info['sub_property']);
          foreach ($parts as $part) {
            $value = $value[$part];
          }
        }
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

  /**
   * If no sort could be applied via Search API, then sort the results manually.
   *
   * This is a last resource thing and arguably a good idea. If the results are
   * paginated it can lead to unexpected results.
   *
   * @param $available_keys
   *   The available keys on the results array.
   *
   * @param $results
   *   The array of search results from Search API.
   */
  protected function manualArraySort($available_keys, &$results) {
    $sorts = $this->parseRequestForListSort();
    $sorts = $sorts ? $sorts : $this->defaultSortInfo();
    foreach ($sorts as $sort => $direction) {
      // Since this is an expensive operation only apply the first sort.
      if (in_array($sort, $available_keys)) {
        usort($results, function ($a, $b) use ($sort, $direction) {
          if ($direction == 'DESC') {
            return $a[$sort] < $b[$sort];
          }
          return $a[$sort] > $b[$sort];
        });
        break;
      }
    }
  }

}
