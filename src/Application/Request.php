<?php

namespace Adept\Application;

use Adept\Interface\Database\DatabaseInterface;
use Adept\DataObject\DataItem\RequestItem;

class Request extends RequestItem
{
  public function __destruct()
  {
    $this->save();
  }
}
