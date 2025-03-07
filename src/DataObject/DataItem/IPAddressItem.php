<?php

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

namespace Adept\DataObject\DataItem;

use Adept\Interface\Database\DatabaseInterface;

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
class IPAddressItem extends \Adept\Abstract\DataObject\AbstractDataItem
{
  protected string $table = 'IPAddress';
  protected string $index = 'ipaddress';

  public string $ipAddress = '';
  public string $encoded;
  public string $status = 'Active';
}
