<?php

namespace Adept\Cache;

use Adept\Configuration;

/**
 * Class MemcachedCache
 *
 * Implements caching using the Memcached extension.
 *
 * @package Adept\Application\Cache
 */
class MemcachedCache implements CacheInterface
{
  /**
   * @var \Memcached
   */
  protected \Memcached $memcached;

  /**
   * Constructor.
   *
   * @param \Memcached|null $memcached Optional Memcached instance.
   */
  public function __construct(Configuration $conf)
  {
    if ($memcached) {
      $this->memcached = $memcached;
    } else {
      $this->memcached = new \Memcached();
      // Default server configuration; can be overridden via the factory.
      $this->memcached->addServer(
        $conf->getString('Cache.Memcached.IPAddress', '127.0.0.1'),
        $conf->getInt('Cache.Memcached.Port', 11211)
      );
    }
  }

  /**
   * {@inheritdoc}
   */
  public function get(string $key)
  {
    return $this->memcached->get($key);
  }

  /**
   * {@inheritdoc}
   */
  public function set(string $key, $value, int $ttl = 0): bool
  {
    return $this->memcached->set($key, $value, $ttl);
  }

  /**
   * {@inheritdoc}
   */
  public function delete(string $key): bool
  {
    return $this->memcached->delete($key);
  }

  /**
   * {@inheritdoc}
   */
  public function clear(): bool
  {
    return $this->memcached->flush();
  }
}
