<?php

namespace Adept\Document\HTML\Head;



class CSS extends \Adept\Abstract\Document\HTML\Head\Asset
{
  protected string $extension = 'css';
  protected string $path = FS_CSS;

  public function getFileTag(string $file, \stdClass $args = null): string
  {
    $tag  = '<link';
    $tag .= ' rel="stylesheet"';
    //$tag .= ' href="' . str_replace(FS_SITE_CACHE, '/', $file) . '"';
    $tag .= ' href="' . str_replace(FS_SITE_CACHE, '/', $file) . '"';
    $tag .= $this->formatArgs($args);
    $tag .= '>';

    return $tag;
  }

  public function getInlineTag(string $contents, \stdClass $args = null): string
  {
    $tag  = '<style' . $this->formatArgs($args) . '>';
    $tag .= $contents;
    $tag .= '</style>';

    return $tag;
  }

  public function addCSS(string $css)
  {
    $this->addInline($css);
  }

  public function minify(string $file): string
  {
    $parser = new \Sabberworm\CSS\Parser(file_get_contents($file));
    $document = $parser->parse();
    return $document->render(\Sabberworm\CSS\OutputFormat::createCompact());
  }
}
