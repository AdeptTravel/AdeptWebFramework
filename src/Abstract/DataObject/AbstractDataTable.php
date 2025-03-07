<?php

namespace Adept\Abstract\Data;

use Adept\Application;

use Adept\Database\Query;
use Adept\Database\QueryBuilder\SelectQueryBuilder;

abstract class AbstractDataTable
{
  protected SelectQueryBuilder $qb;

  /**
   * Columns to filter that are empty.
   *
   * @var array
   */
  protected array $empty = [];

  /**
   * Columns to filter that are not empty.
   *
   * @var array
   */
  protected array $notEmpty = [];

  /**
   * Fields to ignore while applying filters.
   *
   * @var array
   */
  protected array $ignore = ['recursiveLevel'];

  /**
   * Name of the associated database table.
   *
   * @var string
   */
  protected string $table;

  /**
   * Columns to be filtered using SQL `LIKE %`.
   *
   * @var array
   */
  protected array $like = [];

  /**
   * Inner join definitions, formatted as `table => column`.
   *
   * @var array
   */
  protected array $joinInner = [];

  /**
   * Left join definitions, formatted as `table => column`.
   *
   * @var array
   */
  protected array $joinLeft = [];

  protected array $joinColumnMap = [];

  /**
   * The last applied filter.
   *
   * @var array
   */
  protected array $filter = [];

  /**
   * Columns to use for sorting recursive data.
   *
   * @var array
   */
  protected array $recursiveSort = [];

  /**
   * The last dataset returned.
   *
   * @var array
   */
  protected array $data;

  /**
   * Column to sort by.
   *
   * @var string
   */
  public string $sort = '';

  public int $limit  = 0;
  public int $offset = 0;

  /**
   * Sorting direction, default is 'ASC' (ascending).
   *
   * @var string
   */
  public string $dir = 'ASC';

  public int $recursiveLevel;

  /**
   * Identifier for the table item.
   *
   * @var int
   */
  public int $id;

  /**
   * Status of the record, options: 'Active', 'Block', or 'Error'.
   *
   * @var string
   */
  public string $status;

  /**
   * Date when the record was created.
   *
   * @var string
   */
  public string $createdAt;

  public string $updateAt;

  /**
   * Date when the record was last updated.
   *
   * @var string
   */
  public string $updatedAt;

  public function __construct()
  {
    //$get  = Application::getInstance()->session->request->data->get;
    $conf   = Application::getInstance()->conf->data->table;
    $params = Application::getInstance()->params;

    //$this->limit  = $get->getInt('limit', $conf->limit);
    //$this->offset = ($get->getInt('page', 0) * $this->limit);
    $this->limit  = $params->getInt('limit', $conf->limit);
    $this->offset = ($params->getInt('page', 0) * $this->limit);
    // TODO: Create multi-sort options
    $this->sort   = $params->getString('sort', $this->sort);
    $this->dir    = strtoupper($params->getString('dir', $this->dir));
  }

  /**
   * Get data from the table, applying filters and joins.
   * Optionally, recursive data retrieval can be enabled.
   *
   * @param bool $recursive Flag to determine if the query should be recursive.
   * @return array The data fetched based on the filters and settings.
   */
  public function getData(bool $recursive = false): array
  {
    $app    = Application::getInstance();
    $conf   = $app->conf->data->table;
    $data   = [];
    $filter = get_object_vars($this);
    $cache  = hash('sha256', json_encode($filter, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

    if ($conf->cache && apcu_exists($cache)) {
      $data = apcu_fetch($cache);
    } else {

      $select = [];
      $tables = array_merge([$this->table], array_keys($this->joinInner), array_keys($this->joinLeft));

      for ($t = 0; $t < count($tables); $t++) {

        $columns = $app->db->getColumns($tables[$t]);

        for ($c = 0; $c < count($columns); $c++) {
          if ($tables[$t] != $this->table && $columns[$c] == 'id') {
            continue;
          }

          $select[] = $tables[$t] . '.' . $columns[$c] . ' AS ';

          if ($tables[$t] == $this->table) {
            $select[count($select) - 1] .= $columns[$c];
          } else {
            $select[count($select) - 1] .= lcfirst($tables[$t]) . ucfirst($columns[$c]);
          }
        }
      }

      $qb = Query::table($this->table)
        ->select($select);

      if (!empty($this->joinLeft)) {
        foreach ($this->joinLeft as $table => $id) {
          $qb->leftJoin($table, $table, $id . ' = ' . $table . '.' . 'id');
        }
      }

      if (!empty($this->joinInner)) {
        foreach ($this->joinInner as $table => $id) {
          $qb->innerJoin($table, $table, $id . ' = ' . $table . '.' . 'id');
        }
      }

      $columns = $app->db->getColumns($this->table);

      foreach ($filter as $k => $v) {
        if (in_array($k, ['dir', 'empty', 'filter', 'id', 'ignore', 'joinInner', 'joinLeft', 'joinColumnMap', 'like', 'limit', 'notEmpty', 'offset', 'recursiveLevel', 'recursiveSort', 'sort', 'table'])) {
          continue;
        }

        $o = (in_array($k, $this->like)) ? 'LIKE' : '=';

        if (in_array($k, $columns)) {
          $k = $this->table . '.' . $k;
        } else if (array_key_exists($k, $this->joinColumnMap)) {
          $k = $this->joinColumnMap[$k];
        }

        $qb->where($k, $o, $v);
      }

      if ($this->limit > 0) {
        $qb->limit($this->limit);
        $qb->offset($this->offset);
      }

      if (!empty($this->dir)) {
        $dir = strtoupper($this->dir);
      } else if (empty($this->dir) || ($this->dir != 'ASC' && $this->dir != 'DESC')) {
        $this->dir = 'ASC';
      }

      if (empty($sort) && property_exists($this, 'sortOrder')) {
        $sort = 'sortOrder';
      }

      if (!empty($sort) && property_exists($this, $sort)) {
        $qb->orderBy($sort, $dir);
      }

      $data = $qb->execute();
      if ($conf->cache && apcu_exists($cache)) {
        apcu_store($cache, $data, 6000);
      }
    }

    return $data;
  }
}
