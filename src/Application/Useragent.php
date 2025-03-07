<?php

namespace Adept\Application;

use Adept\DataObject\DataItem\UseragentItem;
use Adept\Interface\Database\DatabaseInterface;
use Adept\Application\Params\ParamsContainer;
use WhichBrowser\Parser;

class Useragent extends UseragentItem
{
  public function __construct(DatabaseInterface $db, ParamsContainer $server)
  {
    parent::__construct($db);

    $useragent = $server->getString('HTTP_USER_AGENT');

    if (!$this->loadFromIndex($useragent)) {
      $parser = new Parser($useragent);
      $this->useragent = $useragent;

      if (!empty($parser->toString())) {
        $this->friendly = $parser->toString();
      }

      if (!empty($parser->browser->name)) {
        $this->browser = $parser->browser->name;
      }

      if (!empty($parser->os->alias)) {
        $this->os = $parser->os->alias;
      } else if (!empty($parser->os->name)) {
        $this->os = $parser->os->name;
      }

      if (!empty($parser->device->model)) {
        $this->device = $parser->device->model;
      }

      if (!empty($parser->device->type)) {
        $this->type = $parser->device->type;
      }

      $this->isDetected = $parser->isDetected();

      $this->save();
    }
  }

  public function loadFromIndex(string|int $val): bool
  {
    $val = trim($val);
    $val = filter_var($val, FILTER_UNSAFE_RAW);

    return parent::loadFromIndex($val);
  }
}
