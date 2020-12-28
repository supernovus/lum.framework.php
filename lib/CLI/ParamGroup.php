<?php

namespace Lum\CLI;

/**
 * A class representing a related group of parameters.
 *
 * This is not used by itself, but as a child of the Params class.
 */
class ParamGroup
{
  use ParamOpts;

  const OPT_ALIASES =
  [
    "command" => "is_command",
    "visible" => "is_visible",
    "listed" => "is_listed",
    "listDesc" => "list_desc",
    "usageHeader" => "show_header_in_usage",
    "usageFooter" => "show_footer_in_usage",
    "labelHelp" => "show_label_in_help",
  ];

  const OPT_EXCEPTIONS = ["parent", "name", "varnames"];

  const PARAM_COMMANDS =
  [
    'add_param', 'param', 'toggle', 'flag', 'value', 'optional',
  ];

  // The parent Params object which spawned this.
  protected $parent;

  /**
   * The name of the group.
   *
   * Must be a valid identifier, so alphanumeric and _ only. No  whitespace.
   */
  public $name;

  /**
   * Label for the group.
   *
   * Should be a short description, one line max.
   * Used by getUsage() if is_visible=true
   */
  public $label;

  /**
   * Listing description for the group.
   *
   * Short be a short description, one line max.
   * Used by getUsage() if is_listing=true
   *
   * If set to boolean true, we'll use the value from $this->label;
   * If set to false or null, we won't put a description at all.
   *
   * Constructor aliases: "listDesc"
   */
  public $list_desc = true;

  /**
   * If viewing the group through getHelp() this is the header text shown
   * before the list of parameters.
   *
   * It can contain any printable characters, and can have more than one line.
   * It's completely optional.
   */
  public $header;

  /**
   * If viewing the group through getHelp() this is the footer text shown
   * after the list of parameters.
   *
   * It can contain any printable characters, and can have more than one line.
   * It's also completely optional.
   */
  public $footer;

  /**
   * If this is true, this ParamGroup will be considered a
   * valid Command for the purposes of a script.
   *
   * Commands are an optional feature that can using method routing to
   * determine what actions to apply based on the set of command line
   * arguments specified.
   *
   * Constructor aliases: "command"
   */
  public $is_command = false;

  /**
   * If this is true, the contents of this ParamGroup will be shown
   * in the default help text given by the getUsage() method.
   *
   * Typically getUsage() is called when --help is used by itself with
   * no value specified.
   *
   * Constructor aliases: "visible"
   */
  public $is_visible = false;

  /**
   * If this is true, the name and desc of this group will be shown
   * in the help text as additional topics that can be shown with getHelp().
   *
   * Typically getHelp() is called when --help=<topic> is used on the
   * command line, where <topic> is the group name to get more information on.
   *
   * Constructor aliases: "listed"
   */
  public $is_listed = false;

  /**
   * If this is true, the header will be shown in the getUsage() output.
   *
   * Only applicable if is_visible=true.
   *
   * Constructor aliases: "usageHeader"
   */
  public $show_header_in_usage = false;

  /**
   * If this is true, the footer will be shown in the getUsage() output.
   *
   * Only applicable if is_visible=true.
   *
   * Constructor aliases: "usageFooter"
   */
  public $show_footer_in_usage = false;

  /**
   * If this is true, the label will be shown in the getHelp() output.
   *
   * Constructor aliases: "labelHelp"
   */
  public $show_label_in_help = false;

  /**
   * When using chaining methods, if no 'mandatory' value is specified,
   * this will be used as the default value.
   */
  public $default_mandatory = false;

  /**
   * The formatting plugin used to render the ParamGroup help/usage text.
   *
   * Can either be an object extending \Lum\CLI\ParamGroup\Format,
   * or a string representing the class name. If it is a string then it must
   * have the namespace specified. If no '\' character is detected then the
   * namespace is automatically assumed to be \Lum\CLI\ParamGroup for any
   * formatting plugins we include in this package.
   *
   * Defaults to 'TwoColumn' if not specified at all.
   */
  protected $format = '\Lum\CLI\ParamGroup\TwoColumn';

  // A list of Param objects in this group, indexed by optname.
  // Only one Param with a given "optname" may be in a single group.
  protected $params = [];

  // A map of Param "varname" values to their "mandatory" value.
  protected $varnames = [];

  /**
   * Create a new ParamGroup instance.
   *
   * You probably should never need to call this directly.
   * Use Params::_makeGroup() or Param::group() instead.
   *
   * @param Params $parent   The Params instance that spawned this.
   * @param string $name     The name/identifier for this ParamGroup.
   * @param array  $opts     Optional, any properties in here that have
   *                         corresponding class properties will be populated.
   *
   */
  public function __construct (Params $parent, string $name, array $opts=[])
  {
    if ($parent instanceof Params)
    {
      $this->parent = $parent;
    }
    else
    {
      throw new Exception("Invalid Params instance");
    }

    $this->name = $name;
    $this->__construct_opts($opts, self::OPT_EXCEPTIONS, self::OPT_ALIASES);

    if (is_string($this->format))
    { // A string, call setFormat.
      $this->setFormat($this->format, $opts);
    }
    elseif (!($this->format instanceof ParamGroup\Format))
    {
      throw new Exception("The 'format' must be a ParamGroup\Format subclass");
    }
  }

  /**
   * Set the formatting plugin using a classname.
   *
   * You probably should just pass the "format" option to the constructor
   * instead, as the constructor will call this method if it's specified there.
   *
   * @param string $classname  The classname to load.
   * @param array $opts  Options to pass to the constructor.
   *
   * The specified class must extend the \Lum\CLI\ParamGroup\Format abstract
   * class, otherwise this method will throw an exception.
   *
   * If no PHP namespace is specified in the classname, this will default
   * to \Lum\CLI\ParamGroup as the namespace. This allows using any of the
   * included formatting plugins by just specifying their short name.
   *
   * @return ParamGroup $this
   */
  public function setFormat (string $classname, $opts=[])
  {
    if (!is_string($classname))
    {
      throw new Exception("setFormat only accepts a classname string");
    }

    if (strpos($classname, '\\') === false)
    { // No namespace specified, adding our default.
      $classname = "\\Lum\\CLI\\ParamGroup\\$classname";
    }
    $format = new $classname($this->parent, $this, $opts);

    if ($format instanceof ParamGroup\Format)
    {
      $this->format = $format;
      return $this;
    }
    else
    {
      throw new Exception("The '$classname' class is not a valid formatting plugin");
    }
  }

  /**
   * Change the default mandatory value in a method chaining fashion.
   *
   * @param bool $value  The value to set the 'default_mandatory' property to.
   *                     If not specified, this will toggle between states.
   *
   * @return ParamGroup $this
   */
  public function toggleMandatory ($value=null)
  {
    if (is_null($value))
    {
      $value = !$this->default_mandatory;
    }
    $this->default_mandatory = $value;
    return $this;
  }

  /**
   * Add a parameter to this group.
   *
   * @param Param $param  The Param we are adding to the group.
   * @param boolean $mandatory  Is this a mandatory parameter? 
   *                            If not specified, uses the default_mandatory
   *                            property as the value.
   *
   * To determine if a parameter group is evaluated as matching, all
   * mandatory parameters must be found in the parsed input with non-empty,
   * non-false values.
   * 
   * The optional ones are only for the help/usage text.
   *
   * Multiple Param objects with the same "varname" may be added, but
   * only the first one added will set the $mandatory value. Any subsequent
   * ones will use the $mandatory value from the first one.
   *
   * Only one Param with a specific "optname" may be added to a single group.
   * Attempting to add more than one will result in an Exception being thrown.
   */
  public function addParam (Param $param, $mandatory=null)
  {
    if (!($param instanceof Param))
    {
      throw new Exception("addParam must be passed a Param");
    }
    if (!is_bool($mandatory))
    {
      $mandatory = $this->default_mandatory;
    }

    $opt = $param->optname;

    if (isset($this->params[$opt]))
    {
      $g = $this->name;
      throw new Exception("A Param with the optname '$opt' already in '$g'");
    }

    $this->params[$opt] = $param;

    $this->format->update_layout($param);

    $var = $param->varname;

    if (!isset($this->varnames[$var]))
    {
      $this->varnames[$var] = $mandatory;
    }
  }

  /**
   * Return an array of varnames in this group.
   *
   * @param bool $flat  If true, return a flat array of varnames.
   *                    If false, return an array where the key is the varname
   *                    and the value is a boolean indicating if the varname
   *                    is mandatory in this group. Default: false
   *
   * @return array  The array of varnames.
   */
  public function getVars ($flat=false)
  {
    if ($flat)
    {
      return array_values($this->varnames);
    }
    else
    {
      return $this->varnames;
    }
  }

  /**
   * Return an array of the actual Param objects.
   *
   * @param bool $flat  If true, return a numerically indexed array.
   *                    If false, array will be indexed by optname.
   *                    Default: false
   *
   * @return array  The array of Param objects.
   */
  public function getParams ($flat=false)
  {
    if ($flat)
    {
      return array_values($this->params);
    }
    else
    {
      return $this->params;
    }
  }

  /**
   * Return the first (or only if this is a singleton group) Param.
   *
   * @return Param  The first Param in this group.
   */
  public function getParam ()
  {
    return array_values($this->params)[0];
  }

  /**
   * Given the output of parse() see if all of the mandatory params have
   * a non-empty, and non-false value. This is how we determine if a
   * group of parameters representing a Command was passed.
   *
   * Not generally meant to be called manually. 
   * Use Params::getCommand() instead.
   */
  public function matchesParsed (array $parsedOpts)
  {
    foreach ($this->varnames as $name => $mandatory)
    {
      if (!$mandatory) continue; // Skip optional parameters.
      if (!isset($parsedOpts[$name]) || !$parsedOpts[$name])
      { // The parameter wasn't found, or had a false value.
        return false;
      }
    }

    // If we reached here all variables matched.
    return true;
  }


  /**
   * Render the getUsage text for this group.
   */
  public function renderUsage ()
  {
    $output = '';

    if (isset($this->label))
    {
      $output = str_repeat(' ', $this->parent->indent_labels);
      $output .= $this->label."\n";
    }

    if ($this->show_header_in_usage && isset($this->header))
      $output .= $this->header . "\n";

    $output .= $this->format->render();

    if ($this->show_footer_in_usage && isset($this->footer))
      $output .= $this->footer . "\n";

    return $output;
  }

  /**
   * Render the getHelp text for this group.
   */
  public function renderHelp ()
  {
    $output = '';

    if ($this->show_label_in_help && isset($this->label))
    {
      $output = str_repeat(' ', $this->parent->indent_labels);
      $output .= $this->label."\n";
    }

    if (isset($this->header))
      $output .= $this->header . "\n";

    $output .= $this->format->render();

    if (isset($this->footer))
      $output .= $this->footer . "\n";

    return $output;
  }

  /**
   * Handle unknown method calls.
   *
   * Used for method chaining, any methods not in this class will
   * be assumed to be in the Params class.
   *
   * Methods used to add new Param objects will be handled differently.
   * The first argument may be used as the $mandatory value and will be
   * removed from the argument list if it is boolean.
   *
   * Then the method will be called in the Params instance with the remaining 
   * arguments. Next we get the newly added Param object, 
   * and add it to this group with the $mandatory value from earlier.
   * Finally, we return this ParamGroup instance instead of the Params.
   *
   * Any methods NOT used to add new Param objects will be called directly
   * on the Params and whatever they normally return will be returned.
   *
   * If the method is not found or is not public, an Exception will be thrown.
   */
  public function __call ($command, $args)
  {
    if (in_array($command, self::PARAM_COMMANDS))
    { // Handle with special logic.
      if (is_bool($args[0]))
      { // Mandatory value found.
        $mandatory = array_shift($args);
      }
      else
      { // No mandatory value, use default.
        $mandatory = null;
      }
      $callable = [$this->parent, $command];
      $retval = $this->parent->return_param_on_add;
      $this->parent->return_param_on_add = true;
      $param = call_user_func_array($callable, $args);
      $this->parent->return_param_on_add = $retval;
      $this->addParam($param, $mandatory);
      return $this;
    }
    else
    { // Look for the method in our parent Params and dispatch.
      $callable = [$this->parent, $command];
      if (is_callable($callable))
      {
        return call_user_func_array($callable, $args);
      }
      else
      {
        throw new Exception("Unknown method '$command'");
      }
    }
  }

}