<?php

namespace Adept\Cache;

use Adept\Interface\Cache\CacheInterface;

/**
 * Class APCuCache
 *
 * Implements caching using APCu.
 *
 * Note: Ensure the APCu extension is enabled in your PHP configuration.
 *
 * @package Adept\Application\Cache
 */
class APCuCache implements CacheInterface
{
  /**
   * {@inheritdoc}
   */
  public function get(string $key)
  {
    return apcu_fetch($key);
  }

  /**
   * {@inheritdoc}
   */
  public function set(string $key, $value, int $ttl = 0): bool
  {
    return apcu_store($key, $value, $ttl);
  }

  /**
   * {@inheritdoc}
   */
  public function delete(string $key): bool
  {
    return apcu_delete($key);
  }

  /**
   * {@inheritdoc}
   */
  public function clear(): bool
  {
    return apcu_clear_cache();
  }
}
