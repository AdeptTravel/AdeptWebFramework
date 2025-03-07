<?php

namespace Adept\Database\QueryBuilder;

use Adept\Abstract\Database\AbstractQueryBuilder;

/**
 * Class SelectQueryBuilder
 *
 * Provides a fluent interface for building SELECT SQL queries.
 * Supports selecting columns, joins, grouping, ordering, unions, and more.
 *
 * @package Adept\Database
 */
class SelectQueryBuilder extends AbstractQueryBuilder
{
  /**
   * Array of columns to select.
   *
   * @var array
   */
  protected array $select = [];

  /**
   * Array of JOIN clauses.
   *
   * Each element is an array with keys: type, table, alias, on.
   *
   * @var array
   */
  protected array $joins = [];

  /**
   * Array of GROUP BY columns.
   *
   * @var array
   */
  protected array $groups = [];

  /**
   * Array of HAVING clauses.
   *
   * Each element is an array with:
   * - 'boolean': Logical operator (AND/OR).
   * - 'clause': The SQL clause fragment.
   * - 'params': An array of parameters.
   *
   * @var array
   */
  protected array $havings = [];

  /**
   * Array of ORDER BY clauses.
   *
   * @var array
   */
  protected array $orderBy = [];

  /**
   * Array of UNION clauses.
   *
   * Each element is an array with:
   * - 'type': 'UNION' or 'UNION ALL'.
   * - 'builder': A SelectQueryBuilder instance.
   *
   * @var array
   */
  protected array $unions = [];

  /**
   * The LIMIT for the number of records to return.
   *
   * @var int|null
   */
  protected int $limit;

  /**
   * The OFFSET for the records.
   *
   * @var int|null
   */
  protected int $offset;

  /**
   * Specify the columns to select.
   *
   * @param array $columns The columns to select. Defaults to all columns.
   * @return self
   */
  public function select(array $columns = ['*']): self
  {
    $this->select = $columns;
    return $this;
  }

  /**
   * Add a JOIN clause to the query.
   *
   * @param string $table The table to join.
   * @param string $alias The alias for the joined table.
   * @param string $on The join condition.
   * @param string $type The type of join (INNER, LEFT, RIGHT). Defaults to INNER.
   * @return self
   */
  public function join(string $table, string $alias, string $on, string $type = 'INNER'): self
  {
    $this->joins[] = [
      'type'  => strtoupper($type),
      'table' => $table,
      'alias' => $alias,
      'on'    => $on,
    ];
    return $this;
  }

  /**
   * Add a LEFT JOIN clause to the query.
   *
   * @param string $table The table to join.
   * @param string $alias The alias for the joined table.
   * @param string $on The join condition.
   * @return self
   */
  public function leftJoin(string $table, string $alias, string $on): self
  {
    return $this->join($table, $alias, $on, 'LEFT');
  }

  /**
   * Add a RIGHT JOIN clause to the query.
   *
   * @param string $table The table to join.
   * @param string $alias The alias for the joined table.
   * @param string $on The join condition.
   * @return self
   */
  public function rightJoin(string $table, string $alias, string $on): self
  {
    return $this->join($table, $alias, $on, 'RIGHT');
  }

  /**
   * Add an INNER JOIN clause to the query.
   *
   * @param string $table The table to join.
   * @param string $alias The alias for the joined table.
   * @param string $on The join condition.
   * @return self
   */
  public function innerJoin(string $table, string $alias, string $on): self
  {
    return $this->join($table, $alias, $on, 'INNER');
  }

  /**
   * Add a GROUP BY clause to the query.
   *
   * @param string|array $columns A column or an array of columns to group by.
   * @return self
   */
  public function groupBy($columns): self
  {
    if (is_string($columns)) {
      $columns = [$columns];
    }
    $this->groups = array_merge($this->groups, $columns);
    return $this;
  }

  /**
   * Add a HAVING clause to the query.
   *
   * @param string $column The column name.
   * @param string $operator Comparison operator.
   * @param mixed $value The value to compare.
   * @param string $boolean Logical operator (AND/OR). Defaults to AND.
   * @return self
   */
  public function having(string $column, string $operator, $value, string $boolean = 'AND'): self
  {
    $this->havings[] = [
      'boolean' => $boolean,
      'clause'  => "`$column` $operator ?",
      'params'  => [$value],
    ];
    return $this;
  }

  /**
   * Add an ORDER BY clause to the query.
   *
   * @param string $column The column name.
   * @param string $direction Sort direction (ASC/DESC). Defaults to ASC.
   * @return self
   */
  public function orderBy(string $column, string $direction = 'ASC'): self
  {
    $this->orderBy[] = "`$column` " . strtoupper($direction);
    return $this;
  }

  /**
   * Set the LIMIT for the query.
   *
   * @param int $limit The maximum number of records to return.
   * @return self
   */
  public function limit(int $limit): self
  {
    $this->limit = $limit;
    return $this;
  }

  /**
   * Set the OFFSET for the query.
   *
   * @param int $offset The number of records to skip.
   * @return self
   */
  public function offset(int $offset): self
  {
    $this->offset = $offset;
    return $this;
  }

  /**
   * Add a UNION clause to the query.
   *
   * @param SelectQueryBuilder $builder Another instance of SelectQueryBuilder.
   * @param bool $all Whether to use UNION ALL. Defaults to false.
   * @return self
   */
  public function union(SelectQueryBuilder $builder, bool $all = false): self
  {
    $this->unions[] = [
      'type'    => $all ? 'UNION ALL' : 'UNION',
      'builder' => $builder,
    ];
    return $this;
  }

  /**
   * Build and return the complete SELECT SQL query.
   *
   * @return string The SQL query.
   */
  public function getQuery(): string
  {
    // Trigger before-build hooks.
    $this->triggerBeforeBuildHooks();

    $withClause   = $this->buildWithClause();
    $selectClause = empty($this->select) ? '*' : implode(', ', $this->select);
    $query = $withClause . "SELECT {$selectClause} FROM `{$this->table}`";

    // Append JOIN clauses.
    if (!empty($this->joins)) {
      foreach ($this->joins as $join) {
        $alias = $join['alias'] ? " AS `{$join['alias']}`" : '';
        $query .= " {$join['type']} JOIN `{$join['table']}`{$alias} ON {$join['on']}";
      }
    }

    // Append WHERE clause.
    $whereClause = $this->buildWhereClause();
    if ($whereClause !== '') {
      $query .= " WHERE {$whereClause}";
    }

    // Append GROUP BY clause.
    if (!empty($this->groups)) {
      $query .= " GROUP BY " . implode(', ', $this->groups);
    }

    // Append HAVING clause.
    if (!empty($this->havings)) {
      $havingClause = '';
      foreach ($this->havings as $index => $having) {
        $havingClause .= ($index === 0 ? '' : ' ' . $having['boolean'] . ' ') . $having['clause'];
      }
      $query .= " HAVING {$havingClause}";
    }

    // Append ORDER BY clause.
    if (!empty($this->orderBy)) {
      $query .= " ORDER BY " . implode(', ', $this->orderBy);
    }

    // Append LIMIT and OFFSET.
    if (isset($this->limit)) {
      $query .= " LIMIT {$this->limit}";
    }
    if (isset($this->offset)) {
      $query .= " OFFSET {$this->offset}";
    }

    // Append UNION clauses.
    if (!empty($this->unions)) {
      $mainQuery = $query;
      foreach ($this->unions as $union) {
        $unionQuery = $union['builder']->getQuery();
        $mainQuery .= " " . $union['type'] . " " . $unionQuery;
      }
      $query = $mainQuery;
    }

    // Trigger after-build hooks.
    $this->triggerAfterBuildHooks($query);

    return $query;
  }

  /**
   * Get the parameters to be bound to the SELECT query.
   *
   * @return array The array of query parameters.
   */
  public function getParams(): array
  {
    $params = [];

    // Include parameters from CTE definitions.
    foreach ($this->ctes as $cte) {
      $params = array_merge($params, $cte['query']->getParams());
    }

    // Include parameters from WHERE clauses.
    foreach ($this->wheres as $where) {
      $params = array_merge($params, $where['params']);
    }

    // Include parameters from HAVING clauses.
    foreach ($this->havings as $having) {
      $params = array_merge($params, $having['params']);
    }

    return $params;
  }
}
