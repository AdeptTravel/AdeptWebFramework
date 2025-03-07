<?php

namespace Adept\Interface\Component;

use \Adept\Application;
use \Adept\Interface\Document\DocumentInterface;

interface ComponentInterface
{
  public function __construct(Application $app, DocumentInterface $doc);
  public function getBuffer(): string;
}
