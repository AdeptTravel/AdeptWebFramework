<?php

namespace Adept\Database\QueryBuilder;

use Adept\Abstract\Database\AbstractQueryBuilder;

/**
 * Class UpdateQueryBuilder
 *
 * Provides a fluent interface for building UPDATE SQL queries.
 * Supports setting column values and adding WHERE clauses.
 *
 * Example:
 * <code>
 * $update = UpdateQueryBuilder::table('users')
 *     ->set('email', 'newemail@example.com')
 *     ->where('id', '=', 42);
 * $sql = $update->getQuery();
 * $params = $update->getParams();
 * </code>
 *
 * @package Adept\Database
 */
class UpdateQueryBuilder extends AbstractQueryBuilder
{
  /**
   * Associative array of column => value pairs for the UPDATE query.
   *
   * @var array
   */
  protected array $updateData = [];

  /**
   * Set a column value for the UPDATE query.
   *
   * You can call this method multiple times to update multiple columns.
   *
   * Example:
   * <code>
   * $update->set('email', 'newemail@example.com')
   *        ->set('status', 'active');
   * </code>
   *
   * @param string $column The column name.
   * @param mixed $value The value to set.
   * @return self
   */
  public function set(string $column, $value): self
  {
    $this->updateData[$column] = $value;
    return $this;
  }

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
    $this->updateData = array_merge($this->insertData, $data);
    return $this;
  }

  /**
   * Build and return the complete UPDATE SQL query.
   *
   * This method triggers any registered before- and after-build hooks.
   *
   * @return string The complete UPDATE SQL query.
   */
  public function getQuery(): string
  {
    $this->triggerBeforeBuildHooks();

    $setClauses = [];

    foreach ($this->updateData as $column => $value) {
      $setClauses[] = "`$column` = ?";
    }

    $query = "UPDATE `{$this->table}` SET " . implode(', ', $setClauses);

    $whereClause = $this->buildWhereClause();
    if ($whereClause !== '') {
      $query .= " WHERE " . $whereClause;
    }

    $this->triggerAfterBuildHooks($query);
    return $query;
  }

  /**
   * Get the parameters to be bound to the UPDATE query.
   *
   * This includes the values for the SET clause followed by the parameters from the WHERE conditions.
   *
   * @return array The array of parameters for the UPDATE query.
   */
  public function getParams(): array
  {
    // Start with the update values.
    $params = array_values($this->updateData);

    // Append parameters from WHERE clauses.
    foreach ($this->wheres as $where) {
      $params = array_merge($params, $where['params']);
    }

    return $params;
  }
}
