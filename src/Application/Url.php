<?php

namespace Adept\Application;



use Adept\DataObject\DataItem\UrlItem;
use Adept\Interface\Database\DatabaseInterface;

class Url extends UrlItem
{
  public function __construct(DatabaseInterface $db)
  {
    parent::__construct($db);

    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443)
      ? "https"
      : "http";

    $host = (!empty($_SERVER['HTTP_HOST']))
      ? $_SERVER['HTTP_HOST']
      : $_SERVER['SERVER_NAME'];

    $url = $scheme . '://' . $host . $_SERVER['REQUEST_URI'];

    // Check if scheme or host are missing
    if (($pos = strpos($url, '/') == 0) && $pos !== false) {
      // Missing host
      if (($pos = strpos($url, '//') == 0) && $pos !== false) {
        $url = $scheme . ':' . $url;
      } else {
        $url = $scheme . '://' . $host . $url;
      }
    }

    $url = rtrim(filter_var($url, FILTER_SANITIZE_URL), '/');

    if (!$this->loadFromIndex($url)) {
      $this->parseFromUrl($url);
      $this->save();
    }
  }
}
