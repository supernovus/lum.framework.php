<?php

namespace Lum\Models\Common;

use \Lum\Controllers\Basic as LumCtrl;

/**
 * An abstract base class for Plugins.
 * Feel free to extend as needed!
 */
abstract class Plugin
{
  use \Lum\Meta\ClassID;

  protected $parent;
  protected $ctrl;
  protected $need_ctrl = false;

  public function __construct ($opts)
  {
    if (isset($opts['parent']))
    {
      $parent = $this->parent = $opts['parent'];
    }
    else
    {
      throw new \Exception("Need parent object!");
    }

    if (isset($opts['__classid']))
    {
      $this->__classid = $opts['__classid'];
    }
    else
    {
      throw new \Exception("Need __classid!");
    }

    $GC = 'get_controller';

    if (isset($opts['ctrl']))
    {
      $this->ctrl = $opts['ctrl'];
    }
    elseif (method_exists($parent, $GC) && is_callable([$parent, $GC]))
    {
      $this->ctrl = $parent->get_controller();
    }
    elseif (isset($parent->parent) && $parent->parent instanceof LumCtrl)
    {
      $this->ctrl = $parent->parent;
    }
    elseif ($this->need_ctrl)
    {
      throw new \Exception("Need controller!");
    }
  }
  
}