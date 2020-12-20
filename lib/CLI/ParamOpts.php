<?php

namespace Lum\CLI;

/**
 * A trait that provides a method for the Param* classes constructors to
 * initialize instance properties using an associative array of option
 * names. Exceptions and aliases are also supported.
 */
trait ParamOpts
{
  /**
   * Set instance properties based on an array of options.
   *
   * @param array $opts  The associative array of options passed.
   * @param array $exceptions  A flat array of property/option names to skip.
   * @param array $aliases  An associative array of "alias" => "property".
   *
   */
  protected function __construct_opts (array $opts, 
    array $exceptions=[], array $aliases=[])
  {
    foreach ($opts as $key => $val)
    {
      if (in_array($key, $exceptions)) continue; // Skip exceptions.
      if (isset($aliases[$key])) $key = $aliases[$key]; // Alias found.
      if (property_exists($this, $key))
      { // A matching property was found, let's assign it's value.
        $this->$key = $val;
      }
    }
  }

}