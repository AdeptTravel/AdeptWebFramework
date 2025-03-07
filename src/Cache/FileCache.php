<?php

namespace Adept\Cache;

use Adept\Configuration;
use Adept\Interface\Cache\CacheInterface;

/**
 * Class FileCache
 *
 * Implements caching using the filesystem.
 *
 * Each cache entry is stored in a separate file under a specified directory.
 * The file contains a serialized array with the following keys:
 * - 'expiration': A Unix timestamp when the cache entry expires (or 0 for no expiration).
 * - 'value': The actual cached data.
 *
 * Example usage:
 * <code>
 * $fileCache = new FileCache('/path/to/cache');
 * $fileCache->set('foo', 'bar', 3600);
 * echo $fileCache->get('foo');
 * </code>
 *
 * @package Adept\Application\Cache
 */
class FileCache implements CacheInterface
{
  /**
   * Directory where cache files are stored.
   *
   * @var string
   */
  protected string $cachePath;

  /**
   * Constructor.
   *
   * @param string|null $cachePath The directory to store cache files.
   *                              If not provided, a default directory is used.
   */
  public function __construct(Configuration $config)
  {
    $cachePath = trim($config->getString('Cache.File.Directory', sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'cache'), DIRECTORY_SEPARATOR);

    if (!is_dir($this->cachePath)) {
      mkdir($this->cachePath, 0666, true);
    }
  }

  /**
   * Generate the file path for a given cache key.
   *
   * @param string $key The cache key.
   * @return string The full path to the cache file.
   */
  protected function getCacheFile(string $key): string
  {
    // Hash the key to ensure a valid filename.
    return $this->cachePath . DIRECTORY_SEPARATOR . md5($key) . '.cache';
  }

  /**
   * {@inheritdoc}
   */
  public function get(string $key)
  {
    $file = $this->getCacheFile($key);
    if (!file_exists($file)) {
      return false;
    }
    $data = @file_get_contents($file);

    if ($data === false) {
      return false;
    }

    $cacheItem = @unserialize($data);

    if (!is_array($cacheItem) || !isset($cacheItem['expiration'], $cacheItem['value'])) {
      return false;
    }

    // Check if the item has expired.
    if ($cacheItem['expiration'] !== 0 && time() > $cacheItem['expiration']) {
      @unlink($file);
      return false;
    }

    return $cacheItem['value'];
  }

  /**
   * {@inheritdoc}
   */
  public function set(string $key, $value, int $ttl = 0): bool
  {
    $file = $this->getCacheFile($key);
    $expiration = $ttl > 0 ? time() + $ttl : 0;
    $cacheItem = [
      'expiration' => $expiration,
      'value'      => $value,
    ];
    $data = serialize($cacheItem);
    return file_put_contents($file, $data, LOCK_EX) !== false;
  }

  /**
   * {@inheritdoc}
   */
  public function delete(string $key): bool
  {
    $file = $this->getCacheFile($key);
    if (file_exists($file)) {
      return @unlink($file);
    }
    return false;
  }

  /**
   * {@inheritdoc}
   */
  public function clear(): bool
  {
    $success = true;
    foreach (glob($this->cachePath . DIRECTORY_SEPARATOR . '*.cache') as $file) {
      if (!@unlink($file)) {
        $success = false;
      }
    }
    return $success;
  }
}
