<?php

namespace Adept\Database\QueryBuilder;

use Adept\Abstract\Database\AbstractQueryBuilder;

/**
 * Class InsertQueryBuilder
 *
 * Provides a fluent interface for building INSERT SQL queries.
 * Supports setting values for the insert operation.
 *
 * Example:
 * <code>
 * $query = InsertQueryBuilder::into('users')
 *     ->values(['name' => 'Alice', 'email' => 'alice@example.com']);
 * $sql = $query->getQuery();
 * $params = $query->getParams();
 * </code>
 *
 * @package Adept\Database
 */
class InsertQueryBuilder extends AbstractQueryBuilder
{
  /**
   * Associative array of column => value pairs for the INSERT query.
   *
   * @var array
   */
  protected array $insertData = [];

  /**
   * Set the values for the INSERT query.
   *
   * You can call this method multiple times to merge additional data.
   *
   * @param array $data Associative array of column => value pairs.
   * @return self
   */
  public function values(array $data): self
  {
    $this->insertData = array_merge($this->insertData, $data);
    return $this;
  }

  /**
   * Build and return the complete INSERT SQL query.
   *
   * This method triggers any registered before- and after-build hooks.
   *
   * @return string The complete INSERT SQL query.
   */
  public function getQuery(): string
  {
    $this->triggerBeforeBuildHooks();

    $columns = array_keys($this->insertData);
    $placeholders = array_fill(0, count($columns), '?');
    $query = "INSERT INTO `{$this->table}` (`" . implode('`, `', $columns) . "`) VALUES (" . implode(', ', $placeholders) . ")";

    $this->triggerAfterBuildHooks($query);
    return $query;
  }

  /**
   * Get the parameters to be bound to the INSERT query.
   *
   * @return array The values from the insert data in the order of the columns.
   */
  public function getParams(): array
  {
    return array_values($this->insertData);
  }
}
