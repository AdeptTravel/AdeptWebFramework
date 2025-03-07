<?php

namespace Adept\Application;

use Adept\Application\Params;
use Adept\Configuration;
use Adept\DataObject\DataItem\SessionItem;
use Adept\Interface\Database\DatabaseInterface;

class Session extends SessionItem
{
  protected array $uniqueKeys = ['token'];

  /**
   * Holds session data
   *
   * @var \Adept\Application\Session\Data
   */
  public Params $params;

  public function __construct(DatabaseInterface $db, Configuration $conf, Params $params)
  {
    parent::__construct($db);

    $this->excludeKeys[] = 'params';

    $this->params  = $params;

    $id = $this->params->session->getInt('session.id', 0);

    if ($id == 0) {
      $this->initSession();
    } else {
      $this->loadFromID($id);

      $threshold = new \DateTime(); // Current time
      $threshold->sub(new \DateInterval('PT' . $conf->getInt('session.timeout') . 'S'));

      if (isset($this->updatedAt) && $this->updatedAt >= $threshold) {
        $this->loadCount++;
        $this->save();
      } else {
        $this->initSession();
      }
    }
  }

  public function initSession()
  {
    $this->params->session->purge();
    $this->loadCount = 1;

    if ($this->save()) {

      $this->params->session->set('session.id',                 $this->id);
      $this->params->session->set('session.token',              $this->token);
      $this->params->session->set('session.loadCount',          $this->loadCount);
      $this->params->session->setDateTime('session.createdAt',  $this->createdAt);
      $this->params->session->setDateTime('session.updatedAt',  $this->updatedAt);
    } else {
      die('Error creating session.');
    }
  }
}
