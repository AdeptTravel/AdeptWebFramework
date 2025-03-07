<?php

namespace Adept\Cache;

use Adept\Configuration;

/**
 * Class RedisCache
 *
 * Implements caching using the Redis extension.
 *
 * Note: Ensure the Redis extension is installed and enabled.
 *
 * @package Adept\Application\Cache
 */
class RedisCache implements CacheInterface
{
  /**
   * @var \Redis
   */
  protected \Redis $redis;

  /**
   * Constructor.
   *
   * @param \Redis|null $redis Optional Redis instance.
   */
  public function __construct(Configuration $conf)
  {

    $this->redis = new \Redis();
    // Connect to Redis server; these settings can be customized.
    $this->redis->connect(
      $conf->getString('Cache.Redis.IPAddress', '127.0.0.1'),
      $conf->getInt('Cache.Redis.Port', 6379)
    );
  }

  /**
   * {@inheritdoc}
   */
  public function get(string $key)
  {
    $value = $this->redis->get($key);
    return $value !== false ? unserialize($value) : false;
  }

  /**
   * {@inheritdoc}
   */
  public function set(string $key, $value, int $ttl = 0): bool
  {
    $serializedValue = serialize($value);
    if ($ttl > 0) {
      return $this->redis->setex($key, $ttl, $serializedValue);
    }
    return $this->redis->set($key, $serializedValue);
  }

  /**
   * {@inheritdoc}
   */
  public function delete(string $key): bool
  {
    return $this->redis->del($key) > 0;
  }

  /**
   * {@inheritdoc}
   */
  public function clear(): bool
  {
    return $this->redis->flushDB();
  }
}
