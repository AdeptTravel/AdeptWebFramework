<?php

namespace Adept;

//use Adept\Application\Session\Request\Data\Get;
//use Adept\Application\Session\Request\Route;
//use Adept\Data\Item\URL;


/**
 * Handles base configuration and dynamic overrides.
 */
class Configuration
{
  /**
   * The base configuration data.
   *
   * @var array
   */
  protected array $config = [];

  /**
   * The GET data object.
   *
   * @var get|null
   */
  //protected Get $get;

  /**
   * Constructor.
   *
   * @param array $baseConfig Base configuration values.
   */
  public function __construct(array $baseConfig)
  {
    $this->config = $baseConfig;
  }

  /**
   * Loads dynamic configuration overrides.
   *
   * This method receives the GET, Route, and Url objects (provided by the DI container)
   * and merges in component overrides as well as route parameters.
   *
   * @param Get     $get
   * @param Route   $route
   * @param Url     $url
   * @return void
   */
  /*
  public function loadOverrides(Get $get, Route $route, Url $url): void
  {
    // Store the objects for use in getters.
    $this->get  = $get;

    // Build the expected path for component configuration overrides.
    $componentFile = FS_CORE_COMPONENT
      . "{$route->type}/{$route->component}/{$route->area}/{$url->type}/{$route->view}.Conf.php";

    if (file_exists($componentFile) && is_array($componentConfig = include $componentFile)) {
      $this->config = array_merge($this->config, $componentConfig);
    }

    // Merge route parameters into the configuration.
    $this->config = array_merge($this->config, $route->params);
  }
  */

  /**
   * Retrieves a configuration value as a string.
   *
   * @param string $key The configuration key.
   * @param string $default Default value if the key is not set.
   * @return string
   */
  public function getString(string $key, string $default = ''): string
  {
    return (array_key_exists($key, $this->config)) ? (string)$this->config[$key] : $default;
  }

  /**
   * Retrieves a configuration value as an integer.
   *
   * @param string $key The configuration key.
   * @param int $default Default value if the key is not set.
   * @return int
   */
  public function getInt(string $key, int $default = 0): int
  {
    return (array_key_exists($key, $this->config)) ? (int)$this->config[$key] : $default;
  }

  /**
   * Retrieves a configuration value as a boolean.
   *
   * @param string $key The configuration key.
   * @param bool $default Default value if the key is not set.
   * @return bool
   */
  public function getBool(string $key, bool $default = false): bool
  {
    return (array_key_exists($key, $this->config)) ? (bool)$this->config[$key] : $default;
  }

  /**
   * Retrieves a configuration value as an array.
   *
   * @param string $key The configuration key.
   * @param array $default Default value if the key is not set.
   * @return array
   */
  public function getArray(string $key, array $default = []): array
  {
    return (array_key_exists($key, $this->config)) ? (array)$this->config[$key] : $default;
  }
}
