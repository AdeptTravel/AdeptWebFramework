<?php

namespace Adept\Interface\Database;

use Adept\Database\QueryBuilder\SelectQueryBuilder;
use Adept\Database\QueryBuilder\InsertQueryBuilder;
use Adept\Database\QueryBuilder\UpdateQueryBuilder;

interface DatabaseInterface
{

  public function insert(InsertQueryBuilder $qbs): bool;
  public function insertGetId(InsertQueryBuilder $qbs): int;
  public function insertSingleTableGetId(string $table, array $params): int;
  public function update(UpdateQueryBuilder $qbs): bool;
  public function updateSingleTable(string $table, array $data): bool;
  public function getColumns(string $table): array;
  public function getString(SelectQueryBuilder $qbs): string|null;
  public function getInt(SelectQueryBuilder $qbs): int|null;
  public function getBool(SelectQueryBuilder $qbs): bool|null;
  public function getArray(SelectQueryBuilder $qbs): array|bool;
  public function getObject(SelectQueryBuilder $qbs): object|bool;
  public function getObjects(SelectQueryBuilder $qbs): array|bool;
  public function getLastId(): int;
  public function isDuplicate(string $table, array $data): bool;
}
