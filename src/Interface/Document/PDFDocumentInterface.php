<?php

namespace Adept\Interface\Document;

use Adept\Interface\Database\DatabaseInterface;

interface DocumentInterface
{
  public function __construct(DatabaseInterface $db);
  public function getBuffer();
}
