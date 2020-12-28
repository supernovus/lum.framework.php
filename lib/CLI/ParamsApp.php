<?php

namespace Lum\CLI;

/**
 * An abstract base class for making your own CLI app which can parse
 * command line options using the Params class.
 *
 * Basically create a new class extending this one, define your parameter
 * groups and parameters inside a initialize_params($params) method defined
 * in your class, and then define a handle_{command_name} method for any of the
 * commands, or a handle_params method if you don't want to use the commands
 * feature or want to have a default command if the user doesn't specify
 * any command line options.
 *
 * Since this abstract class is using the HasParams method, simple using:
 *
 *  $params->useHelp()
 *
 * in your initialize_params method will enable the ability to use:
 *
 *  php yourscript.php --help
 *
 * to get a screen of basic usage information and a list of further
 * help topics you can look up, as well as:
 *
 *  php yourscript.php --help={topic}
 *
 * where {topic} is the name of any ParamGroup you defined to show the
 * full information regarding the specific parameter group.
 */
abstract class ParamsApp
{
  use HasParams, HasHelp, HasError;

  /**
   * The initialized parameters. Set in the constructor.
   */
  protected $app_params;

  /**
   * A default prefix for any error messages.
   * This is completely optional, but could be something like "ERROR: "
   * or really anything you want.
   */
  protected $error_prefix = '';

  /**
   * A default usage message for the error() method.
   * Will replace the {help} string literal with the usage command. 
   */
  protected $usage_message = "For usage information use {help}";

  /**
   * A default error message if no valid command was found in the
   * parsed command line options.
   */
  protected $no_command_message = "No valid command specified";

  /**
   * Implement this method in your class to set up the Params object.
   * You can use the public methods in the Params instance to define any
   * parameters and parameter groups for your app using a friendly syntax.
   *
   * @param Params $params  The Params object created by the constructor.
   * @param array $opts  The options passed to the constructor.
   */
  abstract protected function initialize_params (Params $params, array $opts);

  /**
   * A super simple constructor that creates a Params instance,
   * passes it to the initialize_params() method your app
   * class defined with the initialized params.
   *
   * @param array $opts  Options to pass to the Params instance.
   */
  public function __construct (array $opts=[])
  {
    $params = $this->app_params = new Params($opts);
    $this->initialize_params($params, $opts);
  }

  /**
   * A method to return the Params instance. Needed by HasError.
   */
  protected function getParamsInstance ()
  {
    return $this->app_params;
  }

  /**
   * A super simple method that will use the dispatch_params() method
   * to find an appropriate handler method and call it with the parsed
   * parameters. See the HasParams trait for more details.
   *
   * @return mixed  The output from the dispatched method.
   */
  public function run ()
  {
    return $this->dispatch_params($this->app_params);
  }

  /**
   * A default handler for when commands are specified but
   * no valid command can be determined from the command line options.
   *
   * This version simply calls the the error() method with the
   * no_command_message property.
   *
   * Feel free to override in your subclass if so desired.
   */
  protected function handle_default ($opts, $params)
  {
    $this->error($this->no_command_message);
  }

}
