<?php

$plugin = array(
  'resource' => 'basic_search',
  'name' => 'basic_search__1_0',
  'label' => t('Basic Search'),
  'description' => t('Provides basic info doing Search API searches.'),
  'data_provider_options' => array(
    'search_index' => 'default_node_index',
  ),
  'class' => 'RestfulSearchAPIBasicSearch',
  'authentication_types' => TRUE,
  'authentication_optional' => TRUE,
);
