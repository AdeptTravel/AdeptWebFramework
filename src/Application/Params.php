<?php

namespace Adept\Application;

use Adept\Application\Params\ParamsContainer;

/**
 * \Adept\Application\Params
 *
 * Manages session data, including client and server data stores.
 *
 * @package    Adept
 * @author     Brandon J.
 * Yaniz (brandon@adept.travel)
 * @copyright  2021-2024
 * The Adept Traveler, Inc., All Rights Reserved.
 * @license    BSD 2-Clause; See LICENSE.txt
 * @version    1.0.0
 */
class Params
{
  public ParamsContainer $cookie;
  public ParamsContainer $get;
  public ParamsContainer $post;
  public ParamsContainer $server;
  public ParamsContainer $session;

  /**
   * Constructor
   *
   * Initializes the client and server data stores.
   */
  public function __construct()
  {

    // $this->client = ParamsContainer();
    $this->get     = new ParamsContainer($_GET);
    $this->post    = new ParamsContainer($_POST);
    $this->server  = new ParamsContainer($_SERVER);
    $this->session = new ParamsContainer($_SESSION);
  }
}
