<?php

namespace Adept\Interface\Database;

/**
 * Interface QueryBuilderInterface
 *
 * Defines the core methods that all query builder implementations must provide.
 *
 * @package Adept\Database
 */
interface QueryBuilderInterface
{
  /**
   * Build and return the complete SQL query string.
   *
   * @return string The SQL query.
   */
  public function getQuery(): string;

  /**
   * Get the parameters to be bound to the query.
   *
   * @return array The array of query parameters.
   */
  public function getParams(): array;
}
