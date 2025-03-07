<?php

namespace Adept\DataObject\DataItem;

use Adept\Abstract\DataObject\AbstractDataItem;


class UrlItem extends AbstractDataItem
{

  protected string $table = 'Url';
  protected string $index = 'url';

  protected array $excludeKeys = ['raw'];

  /**
   * The full url with QueryString
   *
   * @var string
   */
  public string $raw;

  /**
   * The filtered URL
   *
   * @var string
   */
  public string $url;

  /**
   * The scheme ie. HTTP|HTTPS
   *
   * @var string
   */
  public string $scheme;

  /**
   * The host name
   *
   * @var string
   */
  public string $host;

  /**
   * The path
   *
   * @var string
   */
  public string $path;

  /**
   * The path seperated into an array
   *
   * @var array
   */
  public array $parts = [];

  /**
   * The file for the request, index.html is default
   *
   * @var string
   */
  public string $file;

  /**
   * The extension of the request ie. html|css etc.
   *
   * @var string
   */
  public string $extension;

  /**
   * Type of request 
   *
   * @var string
   */
  public string $type;

  /**
   * Mime type of the request
   *
   * @var string
   */
  public string $mime;

  /**
   * Status - Active, Block, Honeypot
   *
   * @var string
   */
  public string $status = 'Active';


  public function parseFromUrl(string $url)
  {
    if (!filter_var($url, FILTER_VALIDATE_URL)) {
      die('Invalid URL');
    }

    // Remove anchor
    if ($pos = strpos($url, '#')) {
      $url = substr($url, 0, $pos);
    }

    // Remove query
    if ($pos = strpos($url, '?')) {
      $url = substr($url, 0, $pos);
    }

    // Remove trailing /
    if (substr($url, -1) == '/') {
      $url = substr($url, 0, -1);
    }

    if (!$this->loadFromIndex($url)) {
      // Set URL
      $this->url = $url;

      $parsed = parse_url($url);

      $this->scheme = $parsed['scheme'];
      $this->host = $parsed['host'];

      if (!empty($parsed['path'])) {
        $this->path = substr($parsed['path'], 1);
      }

      // Set parts array
      if (!empty($this->path)) {
        $this->parts = explode('/', $this->path);
      }

      // Set index of last element
      $last = count($this->parts) - 1;

      // Set file (if specificed) and extension
      if ($last >= 0 && strpos($this->parts[$last], '.') !== false) {
        $this->file = $this->parts[$last];
        $this->extension = substr($this->parts[$last], strrpos($this->parts[$last], '.') + 1);
      } else {
        $this->extension = 'html';
      }
    }
  }

  public function getFormatInfo(string $extension): object|bool
  {
    $info = (object)[
      'type' => '',
      'mime' => '',
    ];

    // Set type & mime
    // Note: If a type is added make sure it's included in the db tables enum
    switch ($extension) {
      case "css":
        $info->type = "CSS";
        $info->mime = "text/css";
        break;

      case "csv":
        $info->type = "CSV";
        $info->mime = "text/csv";
        break;

      case "eot":
        $info->type = "Font";
        $info->mime = "application/vnd.ms-fontobject";
        break;

      case "gif":
        $info->type = "Image";
        $info->mime = "image/gif";
        break;

      case "html":
        $info->type = "HTML";
        $info->mime = "text/html";
        break;

      case "ico":
        $info->type = "Image";
        $info->mime = "image/vnd.microsoft.icon";
        break;

      case "jpg":
      case "jpeg":
        $info->type = "Image";
        $info->mime = "image/jpeg";
        break;

      case "js":
        $info->type = "JavaScript";
        $info->mime = "text/javascript";
        break;

      case "json":
        $info->type = "JSON";
        $info->mime = "application/json";
        break;

      case "otf":
        $info->type = "Font";
        $info->mime = "font/otf";
        break;

      case "pdf":
        $info->type = "PDF";
        $info->mime = "application/pdf";
        break;

      case "png":
        $info->type = "Image";
        $info->mime = "image/png";
        break;

      case "svg":
        $info->type = "Image";
        $info->mime = "image/svg+xml";
        break;

      case "ttf":
        $info->type = "Font";
        $info->mime = "font/ttf";
        break;

      case "txt":
        $info->type = "Text";
        $info->mime = "text/plain";
        break;

      case "webp":
        $info->type = "Image";
        $info->mime = "image/webp";
        break;

      case "woff":
        $info->type = "Font";
        $info->mime = "font/woff";
        break;

      case "woff2":
        $info->type = "Font";
        $info->mime = "font/woff2";
        break;

      case "xml":
        $info->type = "XML";
        $info->mime = "application/xml";
        break;

      case 'zip':
        $info->type = "Archive";
        $info->mime = "application/zip";
        break;

      case 'gz':
        $info->type = "Archive";
        $info->mime = "application/gzip";
        break;

      case 'mp3':
        $info->type = "Audio";
        $info->mime = "audio/mpeg";
        break;

      case 'mp4':
        $info->type = "Video";
        $info->mime = "video/mp4";
        break;

      default:
        $info = false;
        break;
    }

    return $info;
  }

  public function save(): bool
  {
    $info = $this->getFormatInfo($this->extension);

    if ($info !== false) {
      $this->type = $info->type;
      $this->mime = $info->mime;
    } else {
      die('UrlItem -> Save Error');
      // TODO: Format error
      // Generated on .php and other unknown files.  Within this system it's
      // most likly they are trying to somthing bad.  Let's kill everything
      // and maybe mark the URL as blocked for future bad actors.
      //\Adept\error(debug_backtrace(), 'URL format error', "No idea");
      //\Adept\Error::halt(E_ERROR, 'No idea whats going on', __FILE__, __LINE__);
    }

    return parent::save();
  }
}
