<?php

/**
 * @file
 * Contains \RestfulDataProviderSearchAPI
 */

abstract class RestfulDataProviderSearchAPI extends \RestfulBase implements \RestfulDataProviderSearchAPIInterface {

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
    $required_keys = array(
      // TODO: Add required keys.
    );
    $options = $this->processDataProviderOptions($required_keys);

    // TODO: Set class properties from $options.
  }

  /**
   * {@inheritdoc}
   */
  public function getTotalCount() {
    // TODO.
  }

  public function index() {
    $return = array();
    // TODO.
    return $return;
  }

}
