<?php

/**
 * @file
 * Contains \Drupal\restful_search_api\Plugin\resource\DataProvider\SearchApi.
 */

namespace Drupal\restful_search_api\Plugin\resource\DataProvider;

use Drupal\restful\Exception\BadRequestException;
use Drupal\restful\Exception\ForbiddenException;
use Drupal\restful\Exception\ServerConfigurationException;
use Drupal\restful\Exception\ServiceUnavailableException;
use Drupal\restful\Http\RequestInterface;
use Drupal\restful\Plugin\resource\DataInterpreter\ArrayWrapper;
use Drupal\restful\Plugin\resource\DataInterpreter\DataInterpreterArray;
use Drupal\restful\Plugin\resource\DataProvider\DataProvider;
use Drupal\restful\Plugin\resource\Field\ResourceFieldCollectionInterface;

/**
 * Class SearchApi.
 *
 * @package Drupal\restful_search_api\Plugin\resource\DataProvider
 */
class SearchApi extends DataProvider implements SearchApiInterface {

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
   * {@inheritdoc}
   */
  public function __construct(RequestInterface $request, ResourceFieldCollectionInterface $field_definitions, $account, $plugin_id, $resource_path = NULL, array $options = array(), $langcode = NULL) {
    parent::__construct($request, $field_definitions, $account, $plugin_id, $resource_path, $options, $langcode);
    if (empty($this->options['urlParams'])) {
      $this->options['urlParams'] = array(
        'filter' => TRUE,
        'sort' => TRUE,
        'fields' => TRUE,
        'loadByFieldName' => TRUE,
      );
    }
  }

  /**
   * Return the search index machine name.
   *
   * @return string
   *
   * @throws \Drupal\restful\Exception\ServerConfigurationException
   *   If there is no searchIndex.
   */
  public function getSearchIndex() {
    $options = $this->getOptions();
    if (empty($options['searchIndex'])) {
      throw new ServerConfigurationException('The Search API data provider needs a search index. None found.');
    }
    return $options['searchIndex'];
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
   * {@inheritdoc}
   */
  public function count() {
    throw new ServiceUnavailableException(sprintf('%s is not implemented.', __METHOD__));
  }

  /**
   * {@inheritdoc}
   */
  public function create($object) {
    throw new ServiceUnavailableException(sprintf('%s is not implemented.', __METHOD__));
  }

  /**
   * {@inheritdoc}
   */
  public function viewMultiple(array $identifiers) {
    $return = array();
    foreach ($identifiers as $identifier) {
      try {
        $row = $this->view($identifier);
      }
      catch (ForbiddenException $e) {
        $row = NULL;
      }
      $return[] = $row;
    }

    return array_filter($return);
  }

  /**
   * {@inheritdoc}
   */
  public function update($identifier, $object, $replace = FALSE) {
    throw new ServiceUnavailableException(sprintf('%s is not implemented.', __METHOD__));
  }

  /**
   * {@inheritdoc}
   */
  public function remove($identifier) {
    throw new ServiceUnavailableException(sprintf('%s is not implemented.', __METHOD__));
  }

  /**
   * {@inheritdoc}
   */
  public function getIndexIds() {
    throw new ServiceUnavailableException(sprintf('%s is not implemented.', __METHOD__));
  }

  /**
   * {@inheritdoc}
   */
  protected function initDataInterpreter($identifier) {
    return new DataInterpreterArray($this->getAccount(), new ArrayWrapper((array) $identifier));
  }

  /**
   * {@inheritdoc}
   *
   * @param mixed $identifier
   *   The search query.
   *
   * @return array
   *   If the provided search index does not exist.
   *
   * @throws \Drupal\restful\Exception\ServerConfigurationException If the provided search index does not exist.
   */
  public function view($identifier) {
    // In this case the ID is the search query.
    $options = $output = array();
    // Construct the options array.

    // Set the following options:
    // - offset: The position of the first returned search results relative to
    //   the whole result in the index.
    // - limit: The maximum number of search results to return. -1 means no
    //   limit.
    list($options['offset'], $options['limit']) = $this->parseRequestForListPagination();

    try {
      // Query SearchAPI for the results.
      $search_results = $this->executeQuery($identifier, $options);
      foreach ($search_results as $search_result) {
        $output[] = $this->mapSearchResultToPublicFields($search_result);
      }
    }
    catch (\SearchApiException $e) {
      // Relay the exception with one of RESTful's types.
      throw new ServerConfigurationException(format_string('Search API Exception: @message', array(
        '@message' => $e->getMessage(),
      )));
    }

    // This is an emergency sort. Only apply it if no sort could be applied.
    if (!array_filter(array_values($this->sorted))) {
      $this->manualArraySort($output);
    }

    return $output;
  }

  /**
   * {@inheritdoc}
   */
  protected function parseRequestForListFilter() {
    // At the moment RESTful only supports AND conjunctions.
    $search_api_filter = new \SearchApiQueryFilter('AND');

    $input = $this->getRequest()->getParsedInput();
    if (empty($input['filter'])) {
      // No filtering is needed.
      return $search_api_filter;
    }
    $options = $this->getOptions();
    $url_params = empty($options['urlParams']) ? array() : $options['urlParams'];
    if (empty($url_params['filter'])) {
      throw new BadRequestException('Filter parameters have been disabled in server configuration.');
    }

    foreach ($input['filter'] as $public_field => $value) {
      $resource_field = $this->fieldDefinitions->get($public_field);
      $field = $resource_field->getProperty() ?: $public_field;

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
   * @throws ServerConfigurationException
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
      throw new ServerConfigurationException(format_string('Search API Exception: Unknown index with ID @id.', array(
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
   * @throws BadRequestException
   *
   * @see \RestfulEntityBase::getQueryForList
   */
  protected function queryForListSort(\SearchApiQueryInterface $query) {

    // Get the sorting options from the request object.
    $sorts = $this->parseRequestForListSort();

    $sorts = $sorts ?: $this->defaultSortInfo();

    foreach ($sorts as $sort => $direction) {
      $resource_field = $this->fieldDefinitions->get($sort);
      $property = ($resource_field && $resource_field->getProperty()) ? $resource_field->getProperty() : $sort;
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
    $input = $this->getRequest()->getParsedInput();

    if (empty($input['sort'])) {
      return array();
    }
    $options = $this->getOptions();
    $url_params = empty($options['urlParams']) ? array() : $options['urlParams'];
    if (empty($url_params['sort'])) {
      throw new BadRequestException('Sort parameters have been disabled in server configuration.');
    }

    $sorts = array();
    foreach (explode(',', $input['sort']) as $sort) {
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
    $resource_field_collection = $this->initResourceFieldCollection($result);
    // Loop over all the defined public fields.
    foreach ($this->fieldDefinitions as $public_field_name => $resource_field) {
      // Map result names to public properties.
      /* @var \Drupal\restful_search_api\Plugin\resource\Field\ResourceFieldSearchKeyInterface $resource_field */
      if (!$this->methodAccess($resource_field)) {
        // Allow passing the value in the request.
        continue;
      }
      $resource_field_collection->set($resource_field->id(), $resource_field);
    }

    return $resource_field_collection;
  }

  /**
   * If no sort could be applied via Search API, then sort the results manually.
   *
   * This is a last resource thing and arguably a good idea. If the results are
   * paginated it can lead to unexpected results.
   *
   * @param $results
   *   The array of search results from Search API.
   */
  protected function manualArraySort(&$results) {
    if (empty($results)) {
      return;
    }
    $sorts = $this->parseRequestForListSort();
    $sorts = $sorts ? $sorts : $this->defaultSortInfo();
    foreach ($sorts as $sort => $direction) {
      // Since this is an expensive operation only apply the first sort.
      if ($results[0]->get($sort)) {
        usort($results, function (ResourceFieldCollectionInterface $a, ResourceFieldCollectionInterface $b) use ($sort, $direction) {
          $val1 = $a->get($sort)->render($a->getInterpreter());
          $val2 = $b->get($sort)->render($b->getInterpreter());
          if ($direction == 'DESC') {
            return $val1[$sort] < $val2[$sort];
          }
          return $val1[$sort] > $val2[$sort];
        });
        break;
      }
    }
  }

}
