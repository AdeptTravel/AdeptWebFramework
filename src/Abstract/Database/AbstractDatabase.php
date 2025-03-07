<?php

namespace Adept\Abstract\Database;

use PDO;
use PDOException;

use Adept\Application\Configuration;
use Adept\Database\QueryBuilder\InsertQueryBuilder;
use Adept\Database\QueryBuilder\SelectQueryBuilder;
use Adept\Database\QueryBuilder\UpdateQueryBuilder;
use Adept\Interface\Database\DatabaseInterface;

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

abstract class AbstractDatabase implements DatabaseInterface
{

  protected PDO $pdo;

  abstract protected function execute(string $query, array $params): \PDOStatement|bool;

  /**
   * Process a single parameter.
   *
   * Trims strings, converts booleans to integers, and leaves null values unchanged.
   *
   * @param mixed $value The parameter to process.
   * @return mixed The processed parameter.
   */
  protected function processParam(bool|int|null|string $param): bool|int|null|string
  {
    if (is_bool($param)) {
      return $param ? 1 : 0;
    } else if (is_null($param)) {
      return null;
    }

    return is_string($param) ? trim($param) : $param;
  }

  /**
   * Process an array of parameters.
   *
   * @param array $params The parameters to process.
   * @return array The processed parameters.
   */
  protected function processParams(array $params): array
  {
    return array_map([$this, 'processParam'], $params);
  }

  public function insert(InsertQueryBuilder $qb): bool
  {
    return ($this->execute($qb->getQuery(), $qb->getParams()) !== false);
  }

  public function insertGetId(InsertQueryBuilder $qb): int
  {
    return ($this->execute($qb->getQuery(), $qb->getParams()) === false) ? 0 : $this->getLastId();
  }

  public function insertSingleTableGetId(string $table, array $params): int
  {
    $qbi = InsertQueryBuilder::table($table)->values($params);

    return $this->insertGetId($qbi);
  }

  public function update(UpdateQueryBuilder $qb): bool
  {
    return $this->execute($qb->getQuery(), $qb->getParams());
  }

  public function updateSingleTable(string $table, array $params): bool
  {
    $qbu = UpdateQueryBuilder::table($table)->values($params);
    return $this->execute($qbu->getQuery(), $qbu->getParams());
  }

  /**
   * Get the columns of a table.
   *
   * @param string $table The database table name.
   * @return array|bool Returns an array of column names or false on failure.
   */
  public function getColumns(string $table): array
  {
    $cols = [];

    $key = 'Database.Table.' . $table . '.Columns';

    $stmt = $this->pdo->prepare("DESCRIBE `$table`");
    $stmt->execute();

    $cols =  $stmt->fetchAll(PDO::FETCH_COLUMN);

    return $cols;
  }

  public function getString(SelectQueryBuilder $qbs): string|null
  {
    $result = $this->getValue($qbs);
    return ($result === false) ? null : (string)$result;
  }

  public function getInt(SelectQueryBuilder $qbs): int|null
  {
    $result = $this->getValue($qbs);
    return ($result === false || $result === null) ? null : (int)$result;
  }

  public function getBool(SelectQueryBuilder $qbs): bool|null
  {
    $result = $this->getValue($qbs);
    return ($result === false) ? null : (bool)$result;
  }

  public function getValue(SelectQueryBuilder $qbs): string|int|bool|null
  {
    $stmt = $this->execute($qbs->getQuery(), $qbs->getParams());
    return $stmt->fetchColumn();
  }

  public function getArray(SelectQueryBuilder $qbs, bool $assoc = true): array|bool
  {
    $stmt = $this->execute($qbs->getQuery(), $qbs->getParams());
    $result = $stmt->fetchAll(($assoc) ? PDO::FETCH_ASSOC : PDO::FETCH_NUM);

    return ($result !== false && count($result) > 0) ? $result[0] : false;
  }

  public function getObject(SelectQueryBuilder $qbs): object|bool
  {
    $stmt = $this->execute($qbs->getQuery(), $qbs->getParams());
    return $stmt->fetchObject();
  }

  public function getObjects(SelectQueryBuilder $qbs): array|bool
  {
    $stmt = $this->execute($qbs->getQuery(), $qbs->getParams());
    return $stmt->fetchAll(PDO::FETCH_OBJ);
  }

  public function getLastId(): int
  {
    return (int)$this->pdo->lastInsertId();
  }

  public function isDuplicate(string $table, array $data): bool
  {
    $qbs = SelectQueryBuilder::table($table);
    // Build the WHERE clause.

    $remove = ['createdAt', 'updatedAt', 'status'];
    for ($i = 0; $i < count($remove); $i++) {
      if (array_key_exists($remove[$i], $data)) {
        unset($data[$remove[$i]]);
      }
    }

    foreach ($data as $k => $v) {
      $qbs->where($k, '=', $this->processParam($v));
    }

    return ($this->getInt($qbs) > 0);
  }
}
