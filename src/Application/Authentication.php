<?php

namespace Adept\Application\Session;

use Adept\Application;
use Adept\Application\Session\Data;
use Adept\DataObject\DataItem\UserItem;
use Adept\Interface\Database\DatabaseInterface;

class Authentication
{

  protected Data              $data;
  protected DatabaseInterface $db;
  public int                  $count = 0;
  public \DateTime            $delay;
  public bool                 $status = false;
  public UserItem             $user;

  public function __construct(DatabaseInterface $db, Data &$data)
  {
    $this->db   = $db;
    $this->data = $data;
    $this->user = new UserItem($db);

    // Check if a user ID is stored in session data
    if (($id = $data->server->getInt('auth.userid', 0)) > 0) {
      $this->user->loadFromId($id);

      $this->status = (
        $this->user->id > 0 &&
        $this->user->status == 'Active' &&
        $this->user->verifiedOn != '0000-00-00 00:00:00'
      );
    }
  }

  public function login(string $username, string $password): bool
  {
    if (!$this->status) {
      $result = 'Fail';
      $reason = '';

      $session = Application::getInstance()->session;
      $request = &$session->request;
      $db = Application::getInstance()->db;

      $last = $this->db->getObject(
        'SELECT * FROM `LogAuth` WHERE `sessionId` = ? AND `delay` > NOW() ORDER BY `delay` DESC LIMIT 1',
        [$session->id]
      );

      if ($last !== false) {
        $this->count = $last->failCount;
        $this->count++;
        $this->delay = $this->calcDelay($last->failCount, $last->delay);
        $this->logAuthAttempt($session, $request, $username, 'Delay', '', $this->count, $this->delay->format('Y-m-d H:i:s'));
      } else {
        $this->count = 0;
        $this->delay = new \DateTime();

        // Proceed with login attempt
        $params = [$username];

        // TODO: Move to \Adept\Data\Item\User
        $query  = "SELECT * FROM `User`";
        $query .= " WHERE `username` = ?";
        $query .= " AND `password` <> ''";

        $users = $db->getObjects($query, $params);
        $user  = null;

        $result = 'Fail';
        $reason = 'Nonexistent';

        foreach ($users as $user) {
          if (!empty($user->id)) {
            // Username exists

            if ($user->status == 'Active') {
              // User status is active

              if ($user->verifiedOn != '0000-00-00 00:00:00') {
                // User has been verified
                if (password_verify($password, $user->password)) {
                  // Password matches
                  $this->status = true;

                  $this->data->server->set('auth.userid', $user->id);
                  $this->data->server->set('auth.token', $this->newToken());

                  $session->token = $this->data->server->getString('auth.token');

                  // Log the successful attempt
                  $this->logAuthAttempt($session, $request, $username, 'Success');
                  break;
                } else {
                  // Password is incorrect
                  $result = 'Fail';
                  $reason = 'Password';
                }
              } else {
                // User hasn't verified their email address
                $result = 'Fail';
                $reason = 'Unverified';

                // Break out of the loop
                break;
              }
            } else {
              // User is deactivated via the status value
              $result = 'Fail';
              $reason = 'Deactivated';
            }
          }
        }

        if (!$this->status) {
          // Log the failed attempt
          $this->count++;
          $this->delay = $this->calcDelay($this->count, $this->delay->format('Y-m-d H:i:s'));
          //logAuthAttempt($session, $request, string $username, string $result, int $count = 0, string $reason = '', string $delay = '0000-00-00 00:00:00'): void
          $this->logAuthAttempt($session, $request, $username, $result, $reason, $this->count, $this->delay->format('Y-m-d H:i:s'));
        }
      }
    }

    return $this->status;
  }

  /**
   * Logs out the current user by purging session data
   *
   * @return void
   */
  public function logout()
  {
    $this->data->server->purge();
    $this->status = false;
  }

  /**
   * Generates a new secure token
   *
   * @param int  $length  Length of the token
   * @param bool $lower   Include lowercase letters
   * @param bool $upper   Include uppercase letters
   * @param bool $numbers Include numbers
   *
   * @return string The generated token
   */
  public static function newToken(
    int $length = 32,
    bool $lower = true,
    bool $upper = true,
    bool $numbers = true
  ): string {
    $seed = '';

    if ($lower) {
      $seed .= 'abcdefghijklmnopqrstuvwxyz';
    }

    if ($upper) {
      $seed .= 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    }

    if ($numbers) {
      $seed .= '0123456789';
    }

    if (empty($seed)) {
      \Adept\Error::halt(E_ERROR, 'Error generating a secure token.', __FILE__, __LINE__);
    }

    $count = strlen($seed);
    $token = '';

    // Generate the token using the seed characters
    for ($i = 0; $i < $length; $i++) {
      $token .= $seed[random_int(0, $count - 1)];
    }

    return $token;
  }

  public function tokenValid(string $type, string $token): bool
  {
    $db = \Adept\Application::getInstance()->db;

    return ($db->getInt(
      "SELECT COUNT(*) FROM `user_token` WHERE `type` = ? AND `token` = ? AND `expires` > NOW() ",
      [$type, $token]
    ) > 0);
  }

  //public function tokenCheck(string $type, string $token, string $username, string $password, \DateTime $dob): bool
  public function tokenCheck(string $type, string $token, string $username, string $password): bool
  {
    $db = \Adept\Application::getInstance()->db;
    $status = false;

    $query  = "SELECT a.id, a.password";
    $query .= " FROM `user` AS a";
    $query .= " INNER JOIN `user_token` AS b ON a.id = b.user";
    //$query .= " WHERE a.username = ? AND a.dob = ?";
    $query .= " WHERE a.username = ?";
    $query .= " AND b.type = ? AND b.token = ? AND b.expires > NOW()";

    // Check DB for user
    $user = $db->getObject(
      $query,
      //[$username, $dob->format('Y-m-d 00:00:00'), $type, $token]
      [$username, $type, $token]
    );

    if (is_object($user) && $user->id > 0) {
      if (password_verify($password, $user->password)) {
        // Login
        $this->data->server->set('auth.userid', $user->id);
        $this->user = new User($db, $user->id);
        $this->user->delToken($type);
        $this->status = true;

        // Update status
        $status = true;
      }
    }

    return $status;
  }

  public function tokenExists(string $type, string $token): bool
  {
    $db = \Adept\Application::getInstance()->db;

    return ($db->getInt(
      "SELECT COUNT(*) FROM `user_token` WHERE `type` = ? AND `token` = ? AND `created` < NOW() AND `expires` > NOW()",
      [$type, $token]
    ) == 1);
  }

  protected function calcDelay(int $count, string $date = ''): \DateTime
  {
    $offset = 1;

    for ($i = 0; $i <= $count; $i++) {
      $offset = $offset * 2;
    }
    $now = new \DateTime();
    $delay = new \DateTime($date);
    $delay->add(new \DateInterval("PT{$offset}S"));

    return $delay;
  }

  /**
   * Logs an authentication attempt
   *
   * @param \Adept\Application\Session       $session The current session object
   * @param \Adept\Application\Session\Request $request The current request object
   * @param string                           $username The username attempted
   * @param string                           $result   The result of the attempt ('Success', 'Fail', 'Delay')
   * @param string                           $reason   The reason for the result
   *
   * @return void
   */
  protected function logAuthAttempt($session, $request, string $username, string $result, string $reason = '', int $count = 0, string $delay = '0000-00-00 00:00:00'): void
  {
    $db = Application::getInstance()->db;

    $query = "INSERT INTO `LogAuth`";
    $query .= " (`sessionId`, `requestId`, `useragentId`, `ipAddressId`, `username`, `result`, `reason`, `failCount`, `delay`, `createdAt`)";
    $query .= " VALUES";
    $query .= " (?,?,?,?,?,?,?,?,?,NOW())";

    // Save the request to the DB to get the ID
    $request->save();

    $params = [
      $session->id,
      $request->request->id,
      $request->useragent->id,
      $request->ipAddress->id,
      $username,
      $result,
      $reason,
      $count,
      $delay
    ];

    $db->insert($query, $params);
  }
}
