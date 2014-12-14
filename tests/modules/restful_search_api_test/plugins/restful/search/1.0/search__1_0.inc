<?php

$plugin = array(
  'resource' => 'search',
  'name' => 'search__1_0',
  'label' => t('Search'),
  'description' => t('Provides info doing Search API searches.'),
  'data_provider_options' => array(
    'search_index' => 'test_index',
  ),
  'major_version' => 1,
  'minor_version' => 0,
  'class' => 'RestfulSearchAPISearch',
);
