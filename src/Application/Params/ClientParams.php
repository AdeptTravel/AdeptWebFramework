<?php

/**
 * \Adept\Application\Params\ClientParams
 *
 * Data used for the current request
 *
 * @package    AdeptFramework
 * @author     Brandon J. Yaniz (brandon@adept.travel)
 * @copyright  2021-2024 The Adept Traveler, Inc., All Rights Reserved.
 * @license    BSD 2-Clause; See LICENSE.txt
 */

namespace Adept\Application\Params;

use \Adept\Abstract\AbstractParams;

/**
 * \Adept\Application\Data\ClientParams
 *
 * Data used for the current request
 *
 * @package    AdeptFramework
 * @author     Brandon J. Yaniz (brandon@adept.travel)
 * @copyright  2021-2024 The Adept Traveler, Inc., All Rights Reserved.
 * @license    BSD 2-Clause; See LICENSE.txt
 */
class ClientParams extends AbstractParams
{
  protected string $type = 'Client';
}
