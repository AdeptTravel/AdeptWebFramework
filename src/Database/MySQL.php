<?php

namespace Adept\Database;

use PDO;
use PDOException;

use Adept\Abstract\Database\AbstractDatabase;
use Adept\Configuration;
use Adept\Database\QueryBuilder\InsertQueryBuilder;
use Adept\Database\QueryBuilder\SelectQueryBuilder;
//use Adept\Interface\Database\DatabaseInterface;


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
class MySQL extends AbstractDatabase
{

  protected function execute(string $query, array $params): \PDOStatement|bool
  {
    // TODO: Put query loging here

    $status = false;

    try {
      // Prepare the SQL statement.
      $stmt = $this->pdo->prepare($query);

      // Execute the statement with processed parameters.
      if ($stmt->execute($this->processParams($params))) {
        $status =  $stmt;
      } else {
        $status =  false;
      }
    } catch (\PDOException $e) {
      // Handle exception
      die('Database Error ' . $e->getMessage() .
        '<p>' . $query . '</p>' .
        '<pre>' . print_r($params, true) . '</pre>');
    }

    return $status;
  }

  public function __construct(Configuration $conf)
  {
    // Build the DSN (Data Source Name). Extendable to include port, charset, etc.
    $dsn  = 'mysql:';
    $dsn .= 'host=' . $conf->getString('database.host') . ';';
    $dsn .= 'dbname=' . $conf->getString('database.database') . ';';

    try {
      // Create a new PDO connection with specified options.
      $this->pdo = new PDO(
        $dsn,                        // DSN
        $conf->getString('database.username'),  // Username
        $conf->getString('database.password'), // Password
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
}
