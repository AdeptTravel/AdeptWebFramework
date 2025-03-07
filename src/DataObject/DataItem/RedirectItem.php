<?php

namespace Adept\DataObject\DataItem;

class RedirectItem extends \Adept\Abstract\DataObject\AbstractDataItem
{
  protected string $table = 'Redirect';
  protected string $index = 'route';

  protected array $uniqueKeys = [
    'route'
  ];

  /**
   * The route
   *
   * @param string
   */
  public string $route = '';

  /**
   * Redirect to
   *
   * @var string
   */
  public string $redirect = '';


  /**
   * The HTTP Status code, 301 - Permenent or 302 - Temprorary
   *
   * @var int
   */
  public int $code = 301;

  /**
   * State of the route, ie. Published|Unpublished
   *
   * @param int
   */
  public string $status = 'Active';


  public function save(): bool
  {
    // Remove the / from the begining and end of a string
    $this->route = trim($this->route, '/');

    return parent::save();
  }

  public function formatSegment(string $segment): string
  {
    $segment = strtolower($segment);
    $segment = preg_replace('/[^0-9a-z-]/', '-', $segment);
    $segment = str_replace('--', '-', $segment);

    $parts = explode('-', $segment);
    $count = count($parts);

    for ($i = 0; $i < $count; $i++) {
      if (empty($parts[$i])) {
        unset($parts[$i]);
      }
    }

    $segment = implode('-', $parts);

    return $segment;
  }
}
