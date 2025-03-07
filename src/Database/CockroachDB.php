<?php

namespace Adept\Database;

use PDO;
use PDOException;

use Adept\Configuration;
use Adept\Database\QueryBuilder;
use Adept\Interface\DatabaseInterface;


/**
 * \Adept\Database\MySQL
 *
 * Handles database interactions using PDO
 *
 * @package    AdeptFramework
 * @author     Brandon J. Yaniz
 * @copyright  2021-2024 The Adept Traveler, Inc.
 * @license    BSD 2-Clause; See LICENSE.txt
 * @version    1.1.0
 */
class CockroachDB implements DatabaseInterface
{

  private PDO $pdo;

  public function __construct(Configuration $conf)
  {
    // Build the DSN (Data Source Name). Extendable to include port, charset, etc.
    $dsn  = 'mysql:';
    $dsn .= 'host=' . $conf->getString('Database.Host') . ';';
    $dsn .= 'dbname=' . $conf->getString('database.database') . ';';

    try {
      // Create a new PDO connection with specified options.
      $this->pdo = new PDO(
        $dsn,                        // DSN
        $conf->getString('database.username'),  // Username
        $conf->getString('database.password '), // Password
        [
          PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
          // Using native prepared statements is preferable if supported
          PDO::ATTR_EMULATE_PREPARES   => true,
          PDO::ATTR_PERSISTENT         => true
        ]
      );
    } catch (\PDOException $e) {
      // Throw a custom exception if the connection fails.
      die("Database connection failed: " . $e->getMessage());
    }
  }

  public function insert(QueryBuilder $qb): bool
  {
    return false;
  }

  public function insertGetId(QueryBuilder $qb): int
  {
    return 0;
  }

  public function insertSingleTableGetId(string $table, object $data): int
  {
    return 0;
  }

  public function update(QueryBuilder $qb): bool
  {
    return false;
  }

  public function updateSingleTable(string $table, object $data): bool
  {
    return false;
  }

  public function getString(QueryBuilder $qb): string|null
  {
    return '';
  }

  public function getInt(QueryBuilder $qb): int|null
  {
    return 0;
  }

  public function getBool(QueryBuilder $qb): bool|null
  {
    return false;
  }

  public function getArray(QueryBuilder $qb): array|bool
  {
    return false;
  }

  public function getObject(QueryBuilder $qb): object|bool
  {
    return false;
  }

  public function getObjects(QueryBuilder $qb): array|bool
  {
    return false;
  }

  public function getLastId(): int
  {
    return 0;
  }
  public function isDuplicate(string $table, object $data): bool
  {
    return false;
  }
}
