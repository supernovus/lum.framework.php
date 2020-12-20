<?php

namespace Lum\CLI\ParamGroup;

use \Lum\CLI\{Exception,Params,Param,ParamGroup};

/**
 * An abstract base class and interface for ParamGroup formatting classes.
 */
abstract class Format
{
  protected $params; // The Params grandparent instance.
  protected $group;  // The ParamGroup parent instance.

  /**
   * Build a new formatting plugin.
   *
   * @param Params $params  The Params object at the top of the hiearchy.
   * @param ParamGroup $group  The ParamGroup this is being used by.
   * @param array $opts  (Optional) Any extra plugin-specific options. 
   */
  public function __construct (Params $params, ParamGroup $group, $opts=[])
  {
    $this->params = $params;
    $this->group  = $group;
    $this->initialize_layout($opts);
  }

  /**
   * A method to set up the default layout.
   *
   * @param array $opts  The $opts passed to the constructor.
   */
  abstract protected function initialize_layout ($opts);

  /**
   * A method to update the layout when a Param is added.
   *
   * @param Param $param  The Param object being added.
   */
  abstract public function update_layout (Param $param);

  /**
   * A method to actually render the ParamGroup.
   *
   * @return string  The rendered output representing the group help text.
   */
  abstract public function render ();

}