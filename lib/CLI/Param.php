<?php

namespace Lum\CLI;

/**
 * A simple class representing a parameter for a command line script.
 *
 * This is not used by itself, but as a child of the Params class.
 */
class Param
{
  use ParamOpts;

  const OPT_ALIASES =
  [
    "valued" => "has_value",
    "placeholder" => "value_placeholder",
    "required" => "value_required",
    "multiple" => "toggle_multi",
    "present" => "toggle_true",
    "absent" => "toggle_false",
    "missing" => "value_missing",
    "descValue" => "value_desc",
    "descToggle" => "toggle_desc",
  ];

  const OPT_EXCEPTIONS = ["parent", "optname", "varname"];

  const DEFAULT_VALIDENT = 'value';

  // The parent Params object which spawned this.
  protected $parent;

  /**
   * The name of the option.
   *
   * May be a single character in which case it's a short option.
   * More than one character is a long option.
   *
   * The short options may only be alphanumeric.
   *
   * Long options may have dash (-) or underscore (_) characters as well.
   */
  public $optname;

  /**
   * The variable name for normalized output.
   *
   * If more than one parameter use the same varname, they are considered
   * aliases. This is important to note with command groups as they check
   * the varnames first and only fall back on optname if a varname was not
   * found in the normalized parameters.
   *
   * Must be a valid identifier, so alphanumeric and _ only. No whitespace.
   */
  public $varname;

  /**
   * If true, this parameter takes a value, if false it's a toggle.
   *
   * Constructor aliases: "valued"
   */
  public $has_value = false;

  /**
   * If has_value is true, this will be used in the usage as the identifier.
   * As such it should be short with no special characters. 
   * Whitespace isn't really recommended, but is allowed.
   * Will automatically be surrounded with < > characters for 
   * value_required=true and [ ] characters for value_required=false.
   *
   * Constructor aliases: "placeholder"
   */
  public $value_placeholder = self::DEFAULT_VALIDENT;

  /**
   * If has_value is true, and this is true, the value is required.
   * If this is false, the value may be omitted, in which case it acts
   * as a toggle.
   *
   * Constructor aliases: "required"
   */
  public $value_required = true;

  /**
   * For any toggle parameter, if this is true the parameter can be specified
   * more than once and it's normalized value will always be a numeric value.
   *
   * Constructor aliases: "multiple"
   */
  public $toggle_multi = false;

  /**
   * For a non-multi toggle parameter, this is the value that will be used 
   * in the normalized output if the parameter was specified.
   *
   * Constructor aliases: "present"
   */
  public $toggle_true = true;

  /**
   * For a non-multi toggle parameter, this is the value that will be used
   * in the normalized output if the parameter was not specified.
   *
   * Constructor aliases: "absent"
   */
  public $toggle_false = false;

  /**
   * For a parameter with a required value, if it's not specified at all, 
   * then this will by the default value put into the normalized output.
   *
   * Constructor aliases: "missing"
   */
  public $value_missing = null;

  /**
   * For toggle parameters this is the description used.
   *
   * Constructor aliases: "descToggle"
   */
  public $toggle_desc;

  /**
   * For parameters with values this is the description used.
   *
   * Constructor aliases: "descValue"
   */
  public $value_desc;

  /**
   * Create a new Param instance.
   *
   * You probably should never need to call this directly.
   * Use Params::_makeParam() or Param::param() instead.
   *
   * @param Params $parent   The Params instance that spawned this.
   * @param string $optname  The optname property for this Param.
   * @param string $varname  The varname property for this Param.
   *                         Optional, if not specified we use $optname.
   * @param array  $opts     Optional, any properties in here that have
   *                         corresponding class properties will be populated.
   *
   */
  public function __construct (Params $parent, string $optname, $varname=null, 
    array $opts=[])
  {
    if (strlen($optname) == 0)
    {
      throw new Exception("Param must have a valid optname");
    }

    $this->optname = $optname;

    if (isset($varname))
      $this->varname = $varname;
    else
      $this->varname = $optname;

    $this->__construct_opts($opts, self::OPT_EXCEPTIONS, self::OPT_ALIASES);
  }

  /**
   * Returns true if this is a short parameter.
   */
  public function is_short()
  {
    return (strlen($this->optname) == 1);
  }

  /**
   * Return the full option syntax.
   *
   * This is our 'optname' with the appropriate '-' or '--' prefix.
   */
  public function syntax ()
  {
    return ($this->is_short() ? '-' : '--') . $this->optname;
  }

}