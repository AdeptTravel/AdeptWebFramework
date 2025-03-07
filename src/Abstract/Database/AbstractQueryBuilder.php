<?php

namespace Adept\Abstract\Database;

use Adept\Interface\Database\QueryBuilderInterface;

/**
 * Abstract Class AbstractQueryBuilder
 *
 * Provides common functionality for building SQL queries, including:
 * - Common properties such as table name, WHERE conditions, and CTE definitions.
 * - Methods to build common clauses (WITH and WHERE).
 * - Support for before and after build hooks.
 *
 * Concrete query builder classes (e.g., SelectQueryBuilder, InsertQueryBuilder, etc.)
 * should extend this class and implement the getQuery() and getParams() methods.
 *
 * @package Adept\Database
 */
abstract class AbstractQueryBuilder implements QueryBuilderInterface
{
  /**
   * The table name for the query.
   *
   * @var string
   */
  protected string $table = '';

  /**
   * Array of WHERE conditions.
   *
   * Each element is an array with:
   * - 'boolean': Logical operator (AND/OR).
   * - 'clause': The SQL clause fragment.
   * - 'params': An array of parameters for binding.
   *
   * @var array
   */
  protected array $wheres = [];

  /**
   * Array of Common Table Expressions (CTEs).
   *
   * Each element is an array with:
   * - 'name': The name of the CTE.
   * - 'query': A QueryBuilderInterface instance that builds the CTE query.
   * - 'recursive': Boolean flag indicating if the CTE is recursive.
   *
   * @var array
   */
  protected array $ctes = [];

  /**
   * Array of callbacks to be executed before the query is built.
   *
   * @var array
   */
  protected static array $beforeBuildHooks = [];

  /**
   * Array of callbacks to be executed after the query is built.
   *
   * @var array
   */
  protected static array $afterBuildHooks = [];

  /**
   * Constructor initializes common properties.
   */
  public function __construct()
  {
    $this->wheres = [];
    $this->ctes   = [];
  }

  /**
   * Create an instance of the query builder for a specific table.
   *
   * This static factory method leverages late static binding to ensure that
   * the instance created is of the same type as the class that calls it.
   * It initializes the query builder with the provided table name,
   * allowing for a fluent interface to build queries.
   *
   * Example:
   * <code>
   * $query = SelectQueryBuilder::table('users');
   * </code>
   *
   * @param string $table The name of the table for the query.
   * @return static An instance of the calling query builder class.
   */
  public static function table(string $table): static
  {
    $instance = new static();
    $instance->table = $table;
    return $instance;
  }

  /**
   * Build the WITH clause for Common Table Expressions (CTEs).
   *
   * @return string The complete WITH or WITH RECURSIVE clause.
   */
  protected function buildWithClause(): string
  {
    if (empty($this->ctes)) {
      return '';
    }
    $cteParts    = [];
    $isRecursive = false;

    foreach ($this->ctes as $cte) {
      if ($cte['recursive']) {
        $isRecursive = true;
      }
      $cteParts[] = "`{$cte['name']}` AS (" . $cte['query']->getQuery() . ")";
    }

    $prefix = $isRecursive ? 'WITH RECURSIVE ' : 'WITH ';
    return $prefix . implode(', ', $cteParts) . ' ';
  }

  /**
   * Build the WHERE clause for the query.
   *
   * @return string The complete WHERE clause.
   */
  protected function buildWhereClause(): string
  {
    if (empty($this->wheres)) {
      return '';
    }

    $clause = '';
    foreach ($this->wheres as $index => $where) {
      $clause .= ($index === 0 ? '' : ' ' . $where['boolean'] . ' ') . $where['clause'];
    }

    if (strpos($clause, '.')) {
      $clause = str_replace('.', '`.`', $clause);
    }
    return $clause;
  }

  /**
   * Add a WHERE condition.
   *
   * If the first argument is a callable, it will be used to build a nested condition.
   *
   * @param mixed $column Column name or a callable for nested conditions.
   * @param string|null $operator Comparison operator.
   * @param mixed $value Value to compare.
   * @param string $boolean Logical operator (AND/OR). Defaults to AND.
   * @return self
   */
  public function where($column, string $operator = null, $value = null, string $boolean = 'AND'): self
  {

    if (is_callable($column)) {
      // Create a new instance for nested conditions.
      $nested = new static();
      $column($nested);
      $clause = '(' . $nested->buildWhereClause() . ')';
      if (strpos($clause, '.')) {
        $clause = str_replace('.', '`.`', $clause);
      }
      $this->wheres[] = [
        'boolean' => $boolean,
        'clause'  => $clause,
        'params'  => $nested->getParams(),
      ];
    } else {
      $this->wheres[] = [
        'boolean' => $boolean,
        'clause'  => "`$column` $operator ?",
        'params'  => [$value],
      ];
    }
    return $this;
  }

  /**
   * Add a WHERE condition with OR logic.
   *
   * @param mixed $column Column name or a callable for nested conditions.
   * @param string|null $operator Comparison operator.
   * @param mixed $value Value to compare.
   * @return self
   */
  public function orWhere($column, string $operator = null, $value = null): self
  {
    return $this->where($column, $operator, $value, 'OR');
  }

  /**
   * Add a Common Table Expression (CTE) to the query.
   *
   * @param string $name The name of the CTE.
   * @param QueryBuilderInterface $query The query that defines the CTE.
   * @param bool $recursive Whether this CTE is recursive.
   * @return self
   */
  public function with(string $name, QueryBuilderInterface $query, bool $recursive = false): self
  {
    $this->ctes[] = [
      'name'      => $name,
      'query'     => $query,
      'recursive' => $recursive,
    ];
    return $this;
  }

  /**
   * Register a callback to be executed before the query is built.
   *
   * @param callable $callback A callback function receiving the QueryBuilder instance.
   * @return void
   */
  public static function registerBeforeBuild(callable $callback): void
  {
    self::$beforeBuildHooks[] = $callback;
  }

  /**
   * Register a callback to be executed after the query is built.
   *
   * @param callable $callback A callback function receiving the QueryBuilder instance and the built query.
   * @return void
   */
  public static function registerAfterBuild(callable $callback): void
  {
    self::$afterBuildHooks[] = $callback;
  }

  /**
   * Trigger all registered before-build hooks.
   *
   * @return void
   */
  protected function triggerBeforeBuildHooks(): void
  {
    foreach (self::$beforeBuildHooks as $hook) {
      $hook($this);
    }
  }

  /**
   * Trigger all registered after-build hooks.
   *
   * @param string $query The built query string.
   * @return void
   */
  protected function triggerAfterBuildHooks(string $query): void
  {
    foreach (self::$afterBuildHooks as $hook) {
      $hook($this, $query);
    }
  }

  /**
   * Build and return the complete SQL query string.
   *
   * Concrete classes must implement this method.
   *
   * @return string The SQL query.
   */
  abstract public function getQuery(): string;

  /**
   * Get the parameters to be bound to the query.
   *
   * Concrete classes must implement this method.
   *
   * @return array The array of query parameters.
   */
  abstract public function getParams(): array;
}
