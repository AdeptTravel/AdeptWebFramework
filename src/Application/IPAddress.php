<?php

/**
 * \Adept\Application
 *
 * IP Address object
 *
 * @package    AdeptFramework
 * @author     Brandon J. Yaniz (brandon@adept.travel)
 * @copyright  2021-2024 The Adept Traveler, Inc., All Rights Reserved.
 * @license    BSD 2-Clause; See LICENSE.txt
 */

namespace Adept\Application;

use Adept\Interface\Database\DatabaseInterface;
use Adept\DataObject\DataItem\IPAddressItem;

/**
 * \Adept\DataObject\DataItem\IPAddressItem
 *
 * IP Address object
 *
 * @package    AdeptFramework
 * @author     Brandon J. Yaniz (brandon@adept.travel)
 * @copyright  2021-2024 The Adept Traveler, Inc., All Rights Reserved.
 * @license    BSD 2-Clause; See LICENSE.txt
 */
class IPAddress extends IPAddressItem
{

  /**
   * Undocumented function
   *
   * @return void
   */
  public function __construct(DatabaseInterface $db)
  {
    parent::__construct($db);

    $ipAddress = '';

    if (!empty($_SERVER["HTTP_CF_CONNECTING_IP"])) {
      // Cloudflair
      $ipAddress = $_SERVER["HTTP_CF_CONNECTING_IP"];
    } else if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
      // Primary
      $ipAddress = $_SERVER['HTTP_CLIENT_IP'];
    } else if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
      // Get proxy list
      $list = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);

      // There has to be a better way, this looks like amature hour
      foreach ($list as $ip) {
        if (!empty($ip)) {
          // First IP
          $ipAddress = $ip;
          break;
        }
      }
    } else if (!empty($_SERVER['HTTP_X_FORWARDED'])) {
      $ipAddress = $_SERVER['HTTP_X_FORWARDED'];
    } else if (!empty($_SERVER['HTTP_X_CLUSTER_CLIENT_IP'])) {
      $ipAddress = $_SERVER['HTTP_X_CLUSTER_CLIENT_IP'];
    } else if (!empty($_SERVER['HTTP_FORWARDED_FOR'])) {
      $ipAddress = $_SERVER['HTTP_FORWARDED_FOR'];
    } else if (!empty($_SERVER['HTTP_FORWARDED'])) {
      $ipAddress = $_SERVER['HTTP_FORWARDED'];
    } else if (!empty($_SERVER['REMOTE_ADDR'])) {
      $ipAddress = $_SERVER['REMOTE_ADDR'];
    }

    $ipAddress = filter_var($ipAddress, FILTER_VALIDATE_IP);

    if (!$this->loadFromIndex($ipAddress)) {
      $this->ipAddress = $ipAddress;
      $this->encoded = inet_pton($ipAddress);
      $this->save();
    }
  }
}
