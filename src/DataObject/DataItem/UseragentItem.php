<?php

namespace Adept\DataObject\DataItem;

use Adept\Abstract\DataObject\AbstractDataItem;

class UseragentItem extends AbstractDataItem
{
  protected string $table = 'Useragent';
  protected string $index = 'useragent';

  public string $useragent;
  public string $friendly;
  public string $browser;
  public string $os;
  public string $device;
  public string $type;
  public bool   $isDetected = false;
  public string $status = 'Allow';
}
