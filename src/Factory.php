<?php

namespace Adept;

use Adept\Application;
use Adept\Cache\APCuCache;
use Adept\Cache\FileCache;
use Adept\Cache\MemcachedCache;
use Adept\Cache\NoCache;
use Adept\Cache\RedisCache;
use Adept\Configuration;
use Adept\Database\CockroachDB;
use Adept\Database\MySQL;
use Adept\Interface\Cache\CacheInterface;
use Adept\Interface\Database\DatabaseInterface;
use Adept\Interface\Component\ComponentInterface;
use Adept\Interface\Document\DocumentInterface;
use InvalidArgumentException;


class Factory
{
  public static function createCache(Configuration $conf): CacheInterface
  {
    $cache = null;

    switch ($conf->getString('cache.type')) {
      case 'apcu':
        $cache = new APCuCache();
        break;

      case 'memcached':
        $cache = new MemcachedCache($conf);
        break;

      case 'redis':
        $cache = new RedisCache($conf);
        break;

      case 'file':
        $cache = new FileCache($conf);
        break;
      default:
        throw new InvalidArgumentException("Cache driver '{$driver}' is not supported.");
    }

    return $cache;
  }

  public static function createDatabase(Configuration $config): DatabaseInterface
  {
    return match ($config->getString('Database.Type', 'MySQL')) {
      'MySQL' => new MySQL($config),
      'CockroachDB' => new CockroachDB($config),
      default => throw new \InvalidArgumentException("Unsupported database type: $type"),
    };
  }

  public function createComponent(Application $app, DocumentInterface $doc): ComponentInterface
  {
    $component = "Adept\\Component\\" .
      $app->route->type . "\\" .
      $app->route->component . "\\" .
      $app->route->area . "\\" .
      $app->url->type . "\\" .
      $app->route->view;

    if (!class_exists($component)) {
      // TODO: Throw 404 error
    }

    return new $component($app, $doc);
  }
}
