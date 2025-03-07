<?php

namespace Adept\Abstract\DataObject;

use Adept\Application;
use Adept\Database\QueryBuilder\SelectQueryBuilder;
use Adept\Application\Params\PostParams;
use Adept\Database\QueryBuilder\InsertQueryBuilder;
use Adept\Database\QueryBuilder\UpdateQueryBuilder;
use Adept\Interface\Database\DatabaseInterface;
use stdClass;

abstract class AbstractDataItem
{
  protected DatabaseInterface $db;

  /**
   * Name of the associated database table.
   *
   * @var string
   */
  protected string $table;

  /**
   * The column that can be used for loading outside of the ID column.
   *
   * @var string
   */
  protected string $index = '';

  /**
   * Table columns to filter with the LIKE % SQL command.
   *
   * @var array
   */
  protected array $like = [];

  /**
   * Specifies tables to join (INNER JOIN), as $table => $column.
   *
   * @var array
   */
  protected array $joinInner = [];

  /**
   * Specifies tables to join (LEFT JOIN), as $table => $column.
   *
   * @var array
   */
  protected array $joinLeft = [];

  /**
   * Unique keys for duplicate checking.
   *
   * @var array
   */
  protected array $uniqueKeys = [];

  protected array $excludeKeys = [];

  /**
   * List of public variables to exclude from saving into db on new items.
   *
   * @var array
   */
  protected array $excludeKeysOnNew = [];

  /**
   * The last filter used.
   *
   * @var array
   */
  protected array $filter = [];

  /**
   * The original data, used to compare changes.
   *
   * @var array
   */
  protected array $originalData;

  /**
   * Undocumented variable.
   *
   * @var array
   */
  protected array $postFilters = [];

  /**
   * Array of required fields. (Leave undefined to automatically check all public vars.)
   *
   * @var array
   */
  protected array $required;

  /**
   * Array of errors, used when displaying error messages to users.
   *
   * @var array
   */
  public array $error = [];

  /**
   * The last dataset returned.
   *
   * @var array
   */
  public array $data;

  /**
   * ID of the item in the database table.
   *
   * @var int
   */
  public int $id = 0;

  /**
   * Status – Active, Inactive, Block.
   *
   * @var string
   */
  public string $status;

  /**
   * The created date as a MySQL string.
   *
   * @var \DateTime
   */
  public \DateTime $createdAt;

  /**
   * The last updated date as a MySQL string.
   *
   * @var \DateTime
   */
  public \DateTime $updatedAt;

  public function __construct(DatabaseInterface $db)
  {
    $this->db = $db;
    $this->originalData = [];
    $this->excludeKeys[] = 'createdAt';
    $this->excludeKeys[] = 'updatedAt';
  }

  /**
   * Returns a Query instance preconfigured with the select columns and joins.
   *
   * @return SelectQueryBuilder
   */
  protected function getQuery(): SelectQueryBuilder
  {
    // Get all columns from the main table.
    $columns = $this->db->getColumns($this->table);
    $selectColumns = [];

    // Build select expressions for main table columns.
    foreach ($columns as $col) {
      $selectColumns[] = "`{$this->table}`.`{$col}` AS `{$col}`";
    }

    // Include columns from inner/left joins.
    $joins = array_merge($this->joinInner, $this->joinLeft);

    if (!empty($joins)) {
      foreach ($joins as $joinTable => $joinColumn) {
        $joinCols = $this->db->getColumns($joinTable);

        foreach ($joinCols as $jc) {
          $alias = strtolower($joinTable) . ucfirst($jc);
          $selectColumns[] = "`{$joinTable}`.`{$jc}` AS `{$alias}`";
        }
      }
    }

    // Initialize the Query.
    $qb = SelectQueryBuilder::table($this->table)
      ->select($selectColumns);

    // Add INNER JOIN clauses.
    if (!empty($this->joinInner)) {
      foreach ($this->joinInner as $joinTable => $col) {
        $qb->join($joinTable, '', "`{$this->table}`.`{$col}` = `{$joinTable}`.`id`", 'INNER');
      }
    }

    // Add LEFT JOIN clauses.
    if (!empty($this->joinLeft)) {
      foreach ($this->joinLeft as $joinTable => $col) {
        $qb->leftJoin($joinTable, '', "`{$this->table}`.`{$col}` = `{$joinTable}`.`id`");
      }
    }

    return $qb;
  }

  /**
   * Load an item from the database using its ID.
   *
   * @param int $id
   * @return bool
   */
  public function loadFromID(int $id): bool
  {
    $status = false;

    $qb = $this->getQuery();
    $qb->where('id', '=', $id);

    if (($data = $this->db->getArray($qb)) !== false) {
      $this->loadFromArray($data);
      $status = true;
    }

    return $status;
  }

  /**
   * Load an item based on a unique index.
   *
   * @param string|int $index
   * @return bool
   */
  public function loadFromIndex(string|int $index): bool
  {
    $status = false;

    //$qbs = $this->getQuery()->where($this->index, '=', $index);
    $qbs = SelectQueryBuilder::table($this->table)
      ->where($this->index, '=', $index);
    $data = $this->db->getArray($qbs);

    if (is_array($data = $this->db->getArray($qbs))) {
      $status = true;

      $this->loadFromArray($data);
    }

    return $status;
  }

  /**
   * Load an item based on multiple index conditions.
   *
   * @param array $index
   * @return bool
   */
  public function loadFromIndexes(array $indexes): bool
  {
    $status = false;

    $qb = $this->getQuery();
    // Add each index condition to the Query.
    foreach ($indexes as $k => $v) {
      $qb->where($k, '=', $v);
    }

    if (($data = $this->db->getArray($qb)) !== false) {
      $status = true;

      $this->loadFromArray($data);
    }

    return $status;
  }

  /**
   * Loads the object properties from a database object.
   *
   * @param object $obj
   * @return void
   */
  public function loadFromObj(object $obj)
  {
    $this->data = (array)$obj;

    foreach ($obj as $k => $v) {
      if (!empty($v)) {
        $this->setVar($k, $v);
      }
    }
    $this->originalData = $this->getData();
  }

  public function loadFromArray(array $data)
  {
    $this->data = $data;

    foreach ($data as $k => $v) {
      if (!empty($v)) {
        $this->setVar($k, $v);
      }
    }

    $this->originalData = $this->getData();
  }

  /**
   * Load data from a Post object.
   *
   * @param Post $post
   * @param string $prefix
   * @return void
   */
  public function loadFromPost(PostParams $post, string $prefix = '')
  {
    $this->data = [];

    $reflect    = new \ReflectionClass($this);
    $properties = $reflect->getProperties(\ReflectionProperty::IS_PUBLIC);

    if (!empty($prefix)) {
      $prefix .= '_';
    }

    for ($i = 0; $i < count($properties); $i++) {
      $key = $properties[$i]->name;
      if (in_array($key, ['id', 'createdAt', 'data', 'error'])) {
        continue;
      }
      if ($properties[$i]->getType() !== null) {
        $type = $properties[$i]->getType()->getName();
        if (array_key_exists($key, $this->postFilters)) {
          $method = 'get' . ucfirst($this->postFilters[$key]);
          $this->setVar($key, $post->$method($prefix . $key));
        } else {
          if ($post->exists($prefix . $key)) {
            switch ($type) {
              case 'int':
                $this->$key = $post->getInt($prefix . $key);
                break;
              case 'bool':
                $this->$key = $post->getBool($prefix . $key);
                break;
              case 'string':
                $this->$key = $post->getString($prefix . $key);
                break;
              case 'object':
                $this->$key = $post->getInt($prefix . $key);
                break;
              case 'DateTime':
                $this->$key = $post->getDateTime($prefix . $key);
                break;
              default:
                $this->$key = new $type($post->getInt($prefix . $key));
                break;
            }
            $this->data[$key] = $this->$key;
          }
        }
      }
    }
  }

  /**
   * Saves the current data object in the DB.
   *
   * @return bool
   */
  public function save(): bool
  {
    $status = false;
    $data   = $this->getData();

    for ($i = 0; $i < count($this->excludeKeys); $i++) {
      if (array_key_exists($this->excludeKeys[$i], $data)) {
        unset($data[$this->excludeKeys[$i]]);
      }
    }

    if (count($this->error) == 0) {
      if ($this->hasChanged($data)) {

        if ($this->id == 0) {
          // Insert – first check for duplicate
          if ($this->isDuplicate()) {
            $this->setError('Duplicate', 'The data already exists in table ' . $this->table . '.');
          } else {
            if (($id = $this->db->insertSingleTableGetId($this->table, $data)) !== false) {
              $this->id = $id;
              $status = true;
            }
          }
        } else {
          $qbu = UpdateQueryBuilder::table($this->table);
          $status = $this->db->updateSingleTable($this->table, $data);
          if (!$status) {
            $this->setError('Failed', 'Failed to save the data to table ' . $this->table . '.');
          }
        }
      } else {
        $status = true;
      }
    }

    // We reload the dataitem to refresh auto-generated fields such as createdAt and updatedAt
    if ($status) {
      $this->loadFromId($this->id);
    }

    return $status;
  }

  /**
   * Checks if the object has changed after loading.
   *
   * @param object|null $data
   * @return bool
   */
  public function hasChanged(array $data = []): bool
  {
    if (!empty($data)) {
      $data = $this->getData();
    }

    return ($this->originalData != $data);
  }

  /**
   * Checks for required data.
   *
   * @return bool
   */
  public function isValid(): bool
  {
    /*
    if (isset($this->required)) {
      foreach ($this->required as $k => $v) {
        if (empty($this->$k)) {
          $this->setError($v, $v . ' is a required field.');
        }
      }
    } else if (!empty($this->required)) {
      $reflection = new \ReflectionClass($this);
      $properties = $reflection->getProperties(\ReflectionProperty::IS_PUBLIC);
      foreach ($properties as $p) {
        $key = $p->name;
        if (empty($this->$key)) {
          $key = str_replace('_', ' ', $key);
          $key = ucwords($key);
          $this->setError($key, $key . ' is a required field.');
        }
      }
    }
    return empty($this->error);
    */
    return true;
  }

  /**
   * Checks for duplicate data in the DB based on the uniqueKeys array.
   *
   * @return int|bool
   */
  public function isDuplicate(): int|bool
  {
    $status = false;

    if (!empty($this->uniqueKeys)) {
      $params = [];

      $qbs = SelectQueryBuilder::table($this->table)
        ->select(['id']);

      for ($i = 0; $i < count($this->uniqueKeys); $i++) {
        $key = $this->uniqueKeys[$i];
        $qbs->where($key, '=', (isset($this->$key)) ? $this->$key : null);
      }

      $status = ($this->db->getInt($qbs) > 0);
    } else {

      $status = $this->db->isDuplicate($this->table, $this->getData());
    }

    return $status;
  }

  /**
   * Delete the current item.
   *
   * @return bool
   */
  public function delete(): bool
  {
    /*
    $result = false;

    $qb = UpdateQueryBuilder()::table($this->table);

    if ($this->id > 0) {
      $result = $this->db->delete($this->table, $this->id);
    }
    return $result;
    */
    return false;
  }

  /**
   * Returns the current object data as an object, used when storing data or caching.
   *
   * @param bool $sql
   * @return array
   */
  protected function getData(bool $includeNull = false): array
  {
    $data = [];
    $cols = $this->db->getColumns($this->table);

    for ($i = 0; $i < count($cols); $i++) {
      $key  = $cols[$i];

      if ($key == 'id' && $this->$key == 0) {
        continue;
      }

      if (in_array($key, ['createdAt', 'updatedAt', 'data'])) {
        continue;
      }

      if (isset($this->$key)) {

        switch ($type = gettype($this->$key)) {
          case 'string':
            $data[$key] = trim($this->$key);
            break;
          case 'int':
            $data[$key] = (int)$this->$key;
            break;
          case 'bool':
          case 'boolean':
            $data[$key] = ($this->$key) ? 1 : 0;
            break;
          case 'array':
            $data[$key] = json_encode($this->$key);
            break;
          case 'DateTime':
            $val = $this->$key->format('Y-m-d H:i:s');
            $data[$key] = ($this->$key->format('Y') != '-0001' && $val != '0000-01-01 00:00:00')
              ? $val
              : '0000-00-00 00:00:00';
            break;
          default:
            if (strpos($type, "Adept\\DataObject\\") !== false) {
              $data[$key] = $this->$key->id;
            } else {
              $data[$key] = json_encode($this->$key);
            }
            break;
        }
      } else if ($includeNull || (isset($this->id) && $this->id > 0)) {
        // If this is a new object then we don't set null values, this is
        // only done when saving existing items as the NULL could zero out
        // a field that previously had a value.
        $data[$key] = null;
      }
    }

    return $data;
  }

  /**
   * Sets an error (used on the client side).
   *
   * @param string $title
   * @param string $message
   * @return void
   */
  protected function setError(string $title, string $message)
  {
    $this->error[] = (object)[
      'title'   => $title,
      'message' => $message
    ];
  }

  /**
   * Sets a variable from a key/value pair.
   *
   * @param string $key
   * @param mixed $val
   * @return void
   */
  protected function setVar(string $key, $val)
  {
    if (property_exists($this, $key)) {
      $reflection = new \ReflectionProperty($this, $key);
      $type = $reflection->getType()->getName();

      switch ($type) {
        case 'string':
          $this->$key = (string)$val;
          break;
        case 'int':
          $this->$key = (int)$val;
          break;
        case 'bool':
          $this->$key = (bool)$val;
          break;
        case 'array':
          $this->$key = json_decode($val);
          break;
        case 'DateTime':
          if (strlen($val) == 10) {
            $this->$key = \DateTime::createFromFormat('Y-m-d', $val);
          } else {
            $this->$key = \DateTime::createFromFormat('Y-m-d H:i:s', $val);
          }
          break;
        case 'object':
          $this->$key = json_decode($val);
          break;
        default:
          if (strpos($type, "Adept\\Data\\Item") !== false) {
            $this->$key = new $type($val);
          } else {
            $this->$key = $val;
          }
          break;
      }
    }
  }
}
