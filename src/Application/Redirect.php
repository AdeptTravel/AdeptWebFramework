<?php


namespace Adept\Application;

use Adept\Interface\Database\DatabaseInterface;
use Adept\DataObject\DataItem\RedirectItem;
use Adept\DataObject\DataItem\UrlItem;

class Redirect extends RedirectItem
{
  public function __construct(DatabaseInterface $db, UrlItem $url)
  {
    parent::__construct($db);

    $this->loadFromIndexes([
      'host'  => $url->host,
      'route' => (isset($url->path)) ? $url->path : ''
    ]);
  }
}
