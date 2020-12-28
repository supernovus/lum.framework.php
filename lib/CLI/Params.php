<?php

namespace Lum\CLI;

use \Lum\Text\Util as TU;

/**
 * A class representing the parameters/options for a CLI script.
 *
 * Can parse the command line options using getopt, and can generate
 * usage information.
 */
class Params
{
  use ParamOpts;

  const OPT_EXCEPTIONS = 
  [
    'params', 'param_groups', 'command_groups',
    'visible_groups', 'listed_groups',
  ];

  const OPT_ALIASES =
  [
    "rowWidth" => "max_row_width",
    "indentLabels" => "indent_labels",
    "indentParams" => "indent_params",
    "indentListing" => "indent_listing",
    "listingHeader" => "listing_header",
    "autoChain" => "auto_chain",
  ];

  /**
   * Maximum width of a help text row.
   * By default we use 80 as that's the typical size of a CLI terminal.
   *
   * Constructor aliases: "rowWidth"
   */
  public $max_row_width = 80;

  /**
   * Indent group labels by this many spaces.
   *
   * Constructor aliases: "indentLabels"
   */
  public $indent_labels  = 0;

  /**
   * Indent parameter lines by this many spaces.
   *
   * Constructor aliases: "indentParams"
   */
  public $indent_params  = 2;

  /**
   * Indent help topic listing items by this many spaces.
   *
   * Constructor aliases: "indentListing"
   */
  public $indent_listing = 2;

  /**
   * A header for the list of additional help topics. A reasonable English
   * default is provided here. The string literal {help} will be replaced with
   * the actual registered parameter name to display help topics.
   *
   * Constructor aliases: "listingHeader"
   */
  public $listing_header = "\nFor additional help use {help}=<topic> with one of the following topics:\n";

  /**
   * If this is true then we use ParamGroup chaining when adding
   * groups using the group() or command() method. What this means is the
   * ParamGroup object will be returned instead of the Params object, and
   * any subsequent methods to add parameters will automatically add those
   * parameters to the group.
   *
   * Note that any other method that don't add parameters will directly
   * call the corresponding method on the Params instance, and return the
   * Params instance as the active context in the chain.
   *
   * Constructor aliases: "autoChain"
   */
  public $auto_chain = false;

  /**
   * If this is true, then any of the commands that create and add Param
   * objects will return the new Param object instead of the Params instance.
   *
   * This is used by the auto_chain feature, and isn't really meant for
   * regular use as it breaks the method chaining entirely.
   */
  public $return_param_on_add = false;

  // Minimum column width for the help topic listings.
  public $listing_col_width = 8;

  // If you change any of these, the HasParams trait and ParamsApps abstract
  // class will not work in their default form. Therefore while the ability
  // is still here, I'm leaving it mostly undocumented.
  public $help_standalone  = "help";
  public $usage_standalone = "usage";
  public $help_usage_combo = "help_usage";
  public $help_placeholder = "{help}";

  protected $params = [];           // All param objects.

  protected $param_groups   = [];   // ParamGroup objects indexed by 'name'.
  protected $command_groups = [];   // " " with is_command = true.
  protected $visible_groups = [];   // " " with is_visible = true.
  protected $listed_groups  = [];   // " " with is_listed  = true.

  /**
   * Create a new Params instance.
   *
   * @param array $opts  Optional, any properties in this that have
   *                     corresponding class properties will be populated.
   *                     
   * Additionally supports the following extra options:
   *
   *  "help"         => If specified, passed to useHelp() as the help optname.
   *  "usage"        => If specified, passed to useHelp() as the usage optname.
   *
   */
  public function __construct (array $opts=[])
  {
    $this->__construct_opts($opts, self::OPT_EXCEPTIONS, self::OPT_ALIASES);

    $help = isset($opts['help']) ? $opts['help'] : null;
    $usage = isset($opts['usage']) ? $opts['usage'] : null;

    if (isset($help) || isset($usage))
    {
      $this->useHelp($help, $usage);
    }
  }

  /**
   * Change the auto_chain property value in a method chaining fashion.
   *
   * @param bool $value  The value to set the property to.
   *                     If not specified, this will toggle between states.
   *
   * @return Params $this
   */
  public function autoChain ($value=null)
  {
    if (!is_bool($value))
    {
      $value = !$this->auto_chain;
    }
    $this->auto_chain = $value;
    return $this;
  }

  /**
   * Change the return_param_on_add property value in a method chaining way.
   *
   * @param bool $value  The value to set the property to.
   *                     If not specified, this will toggle between states.
   *
   * @return Params $this
   */
  public function returnParam ($value=null)
  {
    if (!is_bool($value))
    {
      $value = !$this->return_param_on_add;
    }
    $this->return_param_on_add = $value;
    return $this;
  }

  /**
   * Find Param objects matching a specific query.
   *
   * All arguments are optional. If no arguments are supplied, then this
   * method will return all defined Param objects.
   *
   * @param array $query  A map of Param properties that must match.
   *                      If this is empty, return all Param objects.
   *                      Default: []
   *
   * @param bool $first   If this is true return the first matching Param.
   *                      If $query is empty, this argument is ignored.
   *                      Default: false
   * 
   * @return (array|Param)  The output depends on the $first argument.
   *   If $first is false, returns an array of Param objects.
   *   If $first is true, returns the first matching Param object.
   *
   */
  public function getParams (array $query=[], bool $first=false)
  {
    if (count($query) == 0)
    { // No query items. This is the simplest.
      return $this->params;
    }

    $params = [];
    foreach ($this->params as $param)
    {
      foreach ($query as $prop => $value)
      {
        if (!property_exists($param, $prop))
        {
          throw new Exception("Invalid Param property '$prop' in query");
        }
        if ($param->$prop != $value)
        { // The property did not match, skip to the next Param.
          continue 2;
        }
      }

      // If we reached here, all queries matched, hurray.
      if ($first)
      { // Only returning the first matching Param.
        return $param;
      }
      else
      { // Add the Param to the list and continue the search.
        $params[] = $param;
      }
    }

    return $params;
  }

  /**
   * Get all ParamGroup objects.
   *
   * @return array  All ParamGroup objects regardless of type.
   */
  public function getGroups ()
  {
    return $this->param_groups;
  }

  /**
   * A wrapper around getParams() that sets the query to be for the
   * optname property.
   *
   * @param string $optname  (Mandatory) The optname we are searching for.
   * @param bool $first  (Optional) See getParams() for details.
   *
   * @return (array|Param)  See getParams() for details.
   */
  public function getParamsByOpt (string $optname, bool $first=false)
  {
    return $this->getParams(['optname'=>$optname], $first);
  }

  /**
   * A wrapper around getParams() that sets the query to be for the
   * varname property.
   *
   * @param string $varname  (Mandatory) The varname we are searching for.
   * @param bool $first  (Optional) See getParams() for details.
   *
   * @return (array|Param)  See getParams() for details.
   */
  public function getParamsByVar (string $varname, bool $first=false)
  {
    return $this->getParams(['varname'=>$varname], $first);
  }

  /**
   * Add a Param object.
   *
   * Unless you're doing some really custom stuff you shouldn't need to
   * call this manually, but it's here just in case.
   *
   * @param Param $param  The Param object we are adding.
   * @param array $opts   Special options:
   *
   * 'needGroups' => An array of ParamGroup names to add this Param
   *                 to and set the 'mandatory' flag to true.
   *
   * 'wantGroups' => An array of ParamGroup names to add this Param
   *                 to and set the 'mandatory' flag to false.
   *
   * @return Params $this
   *
   * Actually the return value depends on the 'return_param_on_add' property.
   * If it's true, this will return the Param. If it's false it returns the
   * Params instance. As it's not recommended to change that property manually
   * it's not usually an issue, but documenting it anyway.
   */
  public function add_param (Param $param, $opts=[])
  {
    if ($param instanceof Param)
    {
      if (!in_array($param, $this->params, true))
      { // This Param has not already been added, add it.
        $this->params[] = $param;
      }
      else
      {
        throw new Exception("Duplicate Param found");
      }

      if (isset($opts['needGroups']))
      {
        foreach ($opts['needGroups'] as $group)
        {
          $this->add_param_to_group($param, $group, true);
        }
      }

      if (isset($opts['wantGroups']))
      {
        foreach ($opts['wantGroups'] as $group)
        {
          $this->add_param_to_group($param, $group, false);
        }
      }
    }
    else
    {
      throw new Exception("Invalid Param object");
    }

    if ($this->return_param_on_add)
      return $param;

    return $this;
  }

  /**
   * Add a ParamGroup object.
   *
   * Like with add_param(), you shouldn't have to call this manually.
   *
   * @param ParamGroup $group  The ParamGroup object we are adding.
   * @param array $opts  Reserved for future use.
   * @param mixed $chain  (Optional) Enable ParamGroup chaining?
   *                      If true, returns the ParamGroup instead of the
   *                      Params instance. Then subsequent commands to add
   *                      Param objects will automatically add the Param
   *                      objects to the ParamGroup as well, and will require
   *                      a $mandatory argument before the regular arguments.
   *                      If it's not set at all it defaults to the value of
   *                      the 'auto_chain' instance property.
   *
   * @return mixed Either the ParamGroup or Params depending on options.
   */
  public function add_group (ParamGroup $group, $opts=[], $chain=null)
  {
    if (is_null($chain)) $chain = $this->auto_chain;

    if ($group instanceof ParamGroup)
    {
      $name = $group->name;

      if (!isset($this->param_groups[$name]))
      {
        $this->param_groups[$name] = $group;
      }
      else
      {
        throw new Exception("Duplicate ParamGroup found: $name");
      }

      if ($group->is_command && !isset($this->command_groups[$name]))
      {
        $this->command_groups[$name] = $group;
      }

      if ($group->is_visible && !isset($this->visible_groups[$name]))
      {
        $this->visible_groups[$name] = $group;
      }

      if ($group->is_listed && !isset($this->listed_groups[$name]))
      {
        $this->listed_groups[$name] = $group;
        $curWidth = strlen($name);
        $maxWidth = $this->max_row_width / 2; // Max is half the full width.
        if ($curWidth > $maxWidth) throw new Exception("Group name too long");
        if ($curWidth > $this->listing_col_width)
        { // Longer listing name found, update the listing_col_width
          $this->listing_col_width = $curWidth;
        }
      } 
    }
    else
    {
      throw new Exception("Invalid ParamGroup object");
    }

    if ($chain)
      return $group;
    else
      return $this;
  }

  // Add a param to a group.
  protected function add_param_to_group (Param $param, $group, 
    $mandatory)
  {
    if (is_string($group))
    {
      if (isset($this->param_groups[$group]))
      {
        $group = $this->param_groups[$group];
      }
      else
      {
        throw new Exception("Invalid group name specified");
      }
    }
    elseif (!($group instanceof ParamGroup))
    {
      throw new Exception("Invalid group object");
    }

    $group->addParam($param, $mandatory);
  }

  /**
   * Create a Param object and return it.
   *
   * You shouldn't have to call this manually, but just in case you have
   * some custom requirements, it's available.
   *
   * Unlike the majority of public methods in this class, this does not
   * return the $this instance, so cannot be used in chained method calls.
   *
   * @param string $opt  The optname for the Param we are creating.
   * @param string $var  The varname for the Param. Optional.
   * @param array  $opts Optional, any options to pass to the constructor.
   */
  public function _makeParam (string $opt, $var=null, $opts=[])
  {
    return new Param($this, $opt, $var, $opts);
  }

  /**
   * Create a ParamGroup object and return it.
   *
   * Like _makeParam(), you shouldn't have to call this manually.
   * Also like _makeParam() it can't be used in chained method calls.
   *
   * @param string $name  The group name/identifier (ParamGroup::$name)
   * @param array $opts   Optional, any options to pass to the constructor.
   */
  public function _makeGroup ($name, $opts=[])
  {
    return new ParamGroup($this, $name, $opts);
  }

  /**
   * Create a ParamGroup using _makeGroup() and add it with add_group().
   *
   * This uses the same parameters as _makeGroup() and add_group().
   */
  public function group (string $name, $opts=[], $chain=null)
  {
    return $this->add_group($this->_makeGroup($name, $opts), $opts, $chain);
  }

  /**
   * Create a ParamGroup with group() but automatically set the
   * "is_command" => true option.
   *
   * As such it uses the same parameters as group().
   */
  public function command (string $name, $opts=[], $chain=null)
  {
    $opts['is_command'] = true;
    return $this->group($name, $opts, $chain);
  }

  /** 
   * Create a param with _makeParam(), then add it with add_param().
   *
   * This uses the exact same parameters as _makeParam() and also
   * supports any additional options used by add_param().
   *
   * @return Params $this
   */
  public function param (string $opt, $var=null, $opts=[])
  {
    return $this->add_param($this->_makeParam($opt, $var, $opts), $opts);
  }

  /**
   * Add a toggle type parameter.
   *
   * The output from the parse() method will be boolean true if the
   * the parameter was included, or false if it was not.
   *
   * @param string $opt  The optname for the Param.
   * @param string $desc  A description of the Param.
   * @param string $var  (Optional) The varname for the Param.
   * @param array $opts  (Optional) Further options for param().
   *
   * @return Params $this
   */
  public function toggle (string $opt, string $desc, $var=null, array $opts=[])
  {
    $opts['toggle_desc'] = $desc;
    return $this->param($opt, $var, $opts);
  }

  /**
   * Add a flag type parameter.
   *
   * The output from the parse() method will be the number of times the
   * parameter was specified. Which is obviously 0 if it wasn't specified.
   *
   * As long as it's a short parameter (a one character optname), you can 
   * specify it multiple times in two different ways. For example say the
   * optname is 'a' then you could do:
   *
   * ./script.php -a -a                  # $params['a'] => 2
   * ./script.php -aaa                   # $params['a'] => 3
   * ./script.php --nada                 # $params['a'] => 0
   *
   * @param string $opt  The optname for the Param.
   * @param string $desc  A description of the Param.
   * @param string $var  (Optional) The varname for the Param.
   * @param array $opts  (Optional) Further options for param().
   *
   * @return Params $this
   */
  public function flag (string $opt, string $desc, $var=null, array $opts=[])
  {
    $opts['toggle_desc']  = $desc; 
    $opts['toggle_multi'] = true;
    return $this->param($opt, $var, $opts);
  }

  /**
   * Add a parameter with a mandatory value.
   *
   * This is a command line parameter that must have a value specified.
   *
   * @param string $opt  The optname for the Param.
   * @param string $val  The value_placeholder for the Param
   * @param string $desc  A description of the Param.
   * @param string $var  (Optional) The varname for the Param.
   * @param array $opts  (Optional) Additional options to pass to param().
   *
   * @return Params $this
   */
  public function value (string $opt, string $val, string $desc, $var=null,
    array $opts=[])
  {
    $opts['value_desc']        = $desc; 
    $opts['has_value']         = true; 
    $opts['value_required']    = true;
    $opts['value_placeholder'] = $val;
    return $this->param($opt, $var, $opts);
  }

  /**
   * Add a parameter with an optional value.
   *
   * This is a parameter that can have a value or not have a value.
   *
   * @param string $opt  The optname for the Param.
   * @param string $val  The value_placeholder for the Param.
   * @param string $dv  A description of the Param for when it has a value.
   * @param string $dt  A description of the Param when no value is specified.
   * @param string $var  (Optional) The varname for the Param.
   * @param array $opts  (Optional) Additional options to pass to param().
   *
   * The $opts aren't required, but anything supported by param() can be
   * specified. A few common options you might want to specify:
   *
   *  'varname'  The varname for the Param.
   *  'toggle_true'  What to use if the parameter is specified with no value.
   *  'toggle_false'  What to use if the parameter is not specified at all.
   *
   * @return Params $this
   */
  public function optional (string $opt, string $val, string $dv, string $dt, 
    $var=null, $opts=[])
  {
    $opts['has_value']         = true;
    $opts['value_required']    = false;
    $opts['value_desc']        = $dv;
    $opts['toggle_desc']       = $dt;
    $opts['value_placeholder'] = $val;
    return $this->param($opt, $var, $opts);
  }

  /**
   * Set up special arguments for getting help.
   *
   * @param string $help   The optname for the getHelp() command. 
   *                       Optional. Default: "help"
   * @param string $usage  The optname for the getUsage() command.
   *                       Optional. Default: null
   *
   * If $usage isn't specified or is null, it will default to the same name 
   * as $help. If you call useHelp() with no parameters, both will be "help".
   *
   * If $help and $usage have the same name, then one Param with a varname
   * of 'help_usage' will be added with $help as the optname. It will be
   * set to has_value=>true, value_required=>false and added to one ParamGroup also with
   * a name of 'help_usage' with is_command=>true, and the single param will
   * be mandatory. 
   *
   * If $help and $usage have different names, then two Param objects and two
   * ParamGroup objects will be created with 'help' and 'usage' varname/name
   * properties, and $help and $usage optname properties. The 'help' Param
   * will be has_value=>true, whereas the 'usage' Param will be a toggle.
   * Both will be the sole mandatory Param in their corresponding ParamGroup.
   *
   * @return Params $this
   */
  public function useHelp ($help="help", $usage=null)
  {
    if (is_null($usage) && isset($help))
    { // Most common scenario.
      $usage = $help;
    }
    elseif (is_null($help) && isset($usage))
    { // Less common, but still possible.
      $help = $usage;
    }
    elseif (is_null($help) && is_null($usage))
    { // Wait, what? Don't do that, just don't.
      throw new Exception("useHelp(null,null) is not valid");
    }

    $groupOpts = ["is_command"=>true]; // Common ParamGroup opts.

    if ($help === $usage)
    { // One Param to rule them all.
      $this->group($this->help_usage_combo, $groupOpts, true)
        ->param(true, $help, $this->help_usage_combo,
        [
          "has_value" => true,
          "value_required" => false,
        ]);
    }
    else
    { // A powerful duo who will rule together.
      $this->group($this->help_standalone, $groupOpts, true)
        ->param(true, $help, $this->help_standalone,
        [
          "has_value" => true,
        ]);

      $this->group($this->usage_standalone, $groupOpts, true)
        ->param(true, $usage, $this->usage_standalone);
    }

    return $this;
  }

  /**
   * Show a short help message with instructions for how to get
   * further help. This is the output of --help when used alone.
   */
  public function getUsage ()
  {
    $output = '';

    foreach ($this->visible_groups as $group)
    {
      $output .= $group->renderUsage();
    }

    $helpGroup = $this->getHelpGroup();
    if (isset($helpGroup) && count($this->listed_groups) > 0)
    { // Show the listing of additional help topics.
      $helpParam = $helpGroup->getParam();
      $helpFlag = $helpParam->syntax();
      $helpTag = $this->help_placeholder;
      $listTemplate = $this->listing_header;
      $list_header = str_replace($helpTag, $helpFlag, $listTemplate);
      $output .= $list_header;

      $indent = $this->indent_listing;
      $col1width = $this->listing_col_width;
      $col2width = $this->max_row_width - $col1width - $indent;

      foreach ($this->listed_groups as $name => $group)
      {
        if ($group->list_desc === true)
          $desc = $group->label;
        elseif (is_string($group->list_desc))
          $desc = $group->list_desc;
        $output .= str_repeat(' ', $indent);
        $output .= TU::pad(trim($name), $col1width);
        $output .= TU::pad($desc, $col2width);
        $output .= "\n";
      }
    }

    return $output;
  }

  /**
   * Get the getHelp command group if it's been defined.
   * Will look for the standalone 'help' group first, and the
   * combined 'help_usage' group second.
   */
  public function getHelpGroup ()
  {
    if (isset($this->command_groups[$this->help_standalone]))
    {
      return $this->command_groups[$this->help_standalone];
    }
    elseif (isset($this->command_groups[$this->help_usage_combo]))
    {
      return $this->command_groups[$this->help_usage_combo];
    }
  }

  /**
   * Get the getUsage command group if it's been defined.
   * Will look for the standalone 'usage' group first, and the
   * combined 'help_usage' group second.
   */
  public function getUsageGroup ()
  {
    if (isset($this->command_groups[$this->usage_standalone]))
    {
      return $this->command_groups[$this->usage_standalone];
    }
    elseif (isset($this->command_groups[$this->help_usage_combo]))
    {
      return $this->command_groups[$this->help_usage_combo];
    }
  }

  /**
   * Generates the help text for a specific ParamGroup.
   * This is the output of --help=<topic>.
   */
  public function getHelp (string $topic)
  {
    $group = $this->getGroup($topic);
    return $group->renderHelp();
  }

  /**
   * Use our Param objects to generate a getopt statement and return
   * the results from that statement.
   *
   * Noting that PHP's getopt() function has some really bizarre design
   * decisions, such as using 'false' for parameters without arguments.
   *
   * I recommend using parse() instead as it's a more normalized format.
   * In my opinion of course!
   *
   * @param boolean $retStatements  If true, instead of returning the results
   *                                from the getopt() statement, this will
   *                                return a 2 element array representing
   *                                the arguments which would have been
   *                                passed to the getopt() function.
   *                                The need for this seems even less likely
   *                                than calling _getopts() directly, but
   *                                once again, it's here in case it's needed.
   *
   * @param mixed &$optind          If specified, passed by ref to getopt();
   *                                Not used at all if $retStatements=true.
   *
   * @return (array|false)  See PHP's getopt() function for more info.
   */
  public function _getopts ($retStatements=false, &$optind=null)
  {
    $shortOpts = '';
    $longsOpts = [];

    $seen = [];
    foreach ($this->params as $param)
    {
      $opt = $param->optname;

      if (isset($seen[$opt])) continue; // Skip one's we've already seen.
      $seen[$opt] = true; // Don't process this optname again.

      if ($param->has_value)
      {
        if ($param->value_required)
        {
          $opt .= ':';
        }
        else
        {
          $opt .= '::';
        }
      }

      if ($param->is_short())
      { // Add to the shortOpts string.
        $shortOpts .= $opt;
      }
      else
      { // Add to the longOpts array.
        $longOpts[] = $opt;
      }

    }

    if ($retStatements)
    { // Return the getopt statements.
      return [$shortOpts, $longOpts];
    }
    else
    { // Return the output from getopt()
      return getopt($shortOpts, $longOpts, $optind);
    }
  }

  /**
   * Run _getopts() and then parse that output further to make it more sane.
   *
   * This method accepts two optional parameters which both default
   * to false if not specified.
   *
   * @return array  An array of parsed options.
   *
   * In addition to the regular parsed options, the following keys
   * will also be added to the options array:
   *
   *  "+args"         => An array of positional arguments. May be empty.
   * 
   */
  public function parse ()
  {
    global $argv;

    $optind  = null;
    $rawopts = $this->_getopts(false, $optind);
    $niceopts = [];

    foreach ($this->params as $param)
    {
      $opt = $param->optname;
      $var = $param->varname;
      $has = $param->has_value;
      $req = $param->value_required;
      $mul = $param->toggle_multi;

      if (isset($rawopts[$opt]))
      { // The option was passed on the command line.
        $val = $rawopts[$opt];
        if ($val === false)
        { 
          if ($mul)
          { // A multi toggle with one element.
            $val = 1;
          }
          else
          { // has_value=false or value_required=false.
            $val = $param->toggle_true;
          }
        }
        elseif (is_array($val))
        { 
          if ($mul)
          { // Multi-toggle, return the number of elements.
            $val = count($val);
          }
        }
      }
      else
      { // The option was NOT passed on the command line.
        $val = ($has && $req) 
          ? $param->value_missing
          : ($mul ? 0 : $param->toggle_false);
      }
      
      $niceopts[$var] = $val;
    }

    // Get the positional arguments.
    $posArgs = array_splice($argv, $optind);
    $niceopts['+args'] = $posArgs;

    return $niceopts;
  }

  /**
   * Return a ParamGroup by it's name.
   *
   * @param string $name  The name of the ParamGroup.
   *
   * @return ParamGroup  The ParamGroup.
   */
  public function getGroup ($name)
  {
    if (isset($this->param_groups[$name]))
    {
      return $this->param_groups[$name];
    }
    else
    {
      throw new Exception("Invalid ParamGroup requested");
    }
  }

  /**
   * Scan all parameter groups listed as Commands and see which one
   * if any has all of it's parameters set to non-empty, non-false values.
   * The first one to match will be returned. If none match returns null.
   *
   * @param array $parsedOpts  Optional: the output from parse().
   * @param bool  $retGroup    Optional: if true returns the ParamGroup object.
   *
   * @return (string|ParamGroup) Either the name of the group, or the instance.
   */
  public function getCommand (array $parsedOpts=null, bool $retGroup=false)
  {
    if (is_null($parsedOpts))
    {
      $parsedOpts = $this->parse();
    }

    foreach ($this->command_groups as $group)
    {
      if ($group->matchesParsed($parsedOpts))
      {
        if ($retGroup)
          return $group;
        else
          return $group->name;
      }
    }
  }

  /**
   * Return if this Params instance has any ParamGroups set as Commands.
   */
  public function hasCommands ()
  {
    return (count($this->command_groups) > 0);
  }

}