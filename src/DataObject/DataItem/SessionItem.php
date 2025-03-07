<?php

namespace Adept\DataObject\DataItem;

use Adept\Abstract\DataObject\AbstractDataItem;

class SessionItem  extends AbstractDataItem
{
  protected string $table     = 'Session';
  public    int    $userId;
  public    string $token;
  public    int    $loadCount;
  public    string $status    = 'Active';
}
