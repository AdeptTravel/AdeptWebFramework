<?php

namespace Adept\Interface\Document;

use Adept\Application;
use Adept\Interface\Database\DatabaseInterface;

interface DocumentInterface
{
  public Head $head;
  public Menu $menu;

  public function __construct(Application $app);
  public function getBuffer();
}
