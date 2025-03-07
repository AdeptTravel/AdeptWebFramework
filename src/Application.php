<?php

namespace Adept;

use Adept\Application\IPAddress;
use Adept\Application\Params;
use Adept\Application\Params\ParamsContainer;
use Adept\Application\Redirect;
use Adept\Application\Request;
use Adept\Application\Route;
use Adept\Application\Session;
use Adept\Application\Url;
use Adept\Application\Useragent;
use Adept\Configuration;
use Adept\Factory;
use Adept\Interface\Database\DatabaseInterface;

class Application
{
  public Configuration     $conf;
  public DatabaseInterface $db;
  public IPAddress         $ipaddress;
  public Params            $params;
  public Redirect          $redirect;
  public Request           $request;
  public Route             $route;
  public Session           $session;
  public Url               $url;
  public Useragent         $useragent;

  public function __construct(array $baseConfig = [])
  {
    session_start();

    $this->conf      = new Configuration($baseConfig);
    $this->db        = Factory::createDatabase($this->conf);
    $this->ipaddress = new IPAddress($this->db);
    $this->params    = new Params();
    $this->session   = new Session($this->db, $this->conf, $this->params);
    $this->url       = new Url($this->db);
    $this->useragent = new Useragent($this->db, $this->params->server);
    $this->redirect  = new Redirect($this->db, $this->url);
    $this->route     = new Route($this->db, $this->url, $this->conf->getString('site.template'));

    $this->request   = new Request($this->db);
    $this->request->sessionId   = $this->session->id;
    $this->request->ipAddressId = $this->ipaddress->id;
    $this->request->useragentId = $this->useragent->id;

    if ($this->route->id > 0) {
      $this->request->routeId = $this->route->id;
    } else if ($this->redirect->id > 0) {
      $this->request->redirectId  = $this->redirect->id;
    } else {
      $this->request->routeId    = 0;
      $this->request->redirectId = 0;
      $this->route->host         = $this->url->host;
      $this->route->route        = $this->url->path;
      $this->route->type         = 'Global';
      $this->route->area         = 'Public';
      $this->route->status       = 'Error';
      $this->route->component    = 'Error';
      $this->route->view         = '404';
      $this->route->template     = '';
    }

    $this->request->urlId = $this->url->id;
    $this->request->code  = 200;
    $this->request->save();

    if ($this->redirect->id > 0) {
      $this->redirect($this->redirect->redirect, $this->redirect->code);
    }
  }

  /**
   * Redirects the request to a new URL
   *
   * @param string $url  The URL to redirect to
   * @param int    $code The HTTP status code for the redirection (default is 301)
   *
   * @return void
   */
  public function redirect(string $url, $code = 301)
  {
    // Set the HTTP response code
    http_response_code($this->request->code);
    // Send the redirect header
    header('Location:' . $url, true, $code);
    // Terminate the script
    die();
  }
}
