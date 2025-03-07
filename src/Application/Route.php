<?php

namespace Adept\Application;

use Adept\Application\Url;
use Adept\DataObject\DataItem\RouteItem;
use Adept\Interface\Database\DatabaseInterface;

class Route extends RouteItem
{

  public function __construct(
    DatabaseInterface $db,
    Url $url,
    string $template
  ) {
    parent::__construct($db);

    // Clean the extension off the path
    $path = (isset($url->path)) ? $url->path : '';
    $ext  = empty($url->extension) ? 'html' : $url->extension;
    $len  = strlen($ext);

    if (substr($path, -$len) == $ext) {
      $path = substr($path, 0, - (strlen($ext) + 1));
    }

    $this->loadFromIndexes(['host' => $url->host, 'route' => $path]);

    if ($this->html && empty($this->template)) {
      $this->template = $template;
    }
  }
}
