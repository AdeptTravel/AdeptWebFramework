<?php

namespace Adept\Interface\Cache;

/**
 * Interface CacheInterface
 *
 * Defines common methods for caching implementations.
 */
interface CacheInterface
{
  /**
   * Retrieve an item from the cache.
   *
   * @param string $key The cache key.
   * @return mixed The cached value, or false if not found.
   */
  public function get(string $key);

  /**
   * Store an item in the cache.
   *
   * @param string $key The cache key.
   * @param mixed $value The value to cache.
   * @param int $ttl Time-to-live in seconds (0 for no expiration).
   * @return bool True on success, false on failure.
   */
  public function set(string $key, $value, int $ttl = 0): bool;

  /**
   * Delete an item from the cache.
   *
   * @param string $key The cache key.
   * @return bool True on success, false on failure.
   */
  public function delete(string $key): bool;

  /**
   * Clear all items from the cache.
   *
   * @return bool True on success, false on failure.
   */
  public function clear(): bool;
}
