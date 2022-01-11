<?php

namespace Lum\Controllers\Auth;

abstract class Plugin
{
  protected $parent;
  public function __construct ($opts=[])
  {
    if (isset($opts['parent']))
      $this->parent = $opts['parent'];
  }
  abstract public function options (mixed $conf): array;
  abstract public function getAuth (mixed $conf, array $opts=[]): ?bool;
}

