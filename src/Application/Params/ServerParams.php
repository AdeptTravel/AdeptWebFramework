<?php

/**
 * \Adept\Application\Params\ServerParams
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
 * \Adept\Application\Params\ServerParams
 *
 * Data used for the current request
 *
 * @package    AdeptFramework
 * @author     Brandon J. Yaniz (brandon@adept.travel)
 * @copyright  2021-2024 The Adept Traveler, Inc., All Rights Reserved.
 * @license    BSD 2-Clause; See LICENSE.txt
 */
class ServerParams extends AbstractParams
{
  protected string $type; = $_SERVER;
}
