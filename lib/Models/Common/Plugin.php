<?php

namespace Lum\Models\Common;

/**
 * An abstract base class for Plugins.
 * Feel free to extend as needed!
 */
abstract class Plugin
{
  use \Lum\Meta\ClassID;

  protected $parent;

  public function __construct ($opts)
  {
    if (isset($opts['parent']))
    {
      $this->parent = $opts['parent'];
    }
    else
    {
      throw new \Exception("Need parent object!");
    }

    if (isset($opts['__classid']))
    {
      $this->__classid = $opts['__classdid'];
    }
    else
    {
      throw new \Exception("Need __classid!");
    }
  }
  
}