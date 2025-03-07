<?php

namespace Adept\DataObject\DataItem;



class RequestItem extends \Adept\Abstract\DataObject\AbstractDataItem
{
  protected string $table = 'Request';
  public    int    $sessionId;
  public    int    $ipAddressId;
  public    int    $useragentId;
  public    int    $routeId;
  public    int    $redirectId;
  public    int    $urlId;
  public    int    $code;
  public    string $status = 'Allow';
}
