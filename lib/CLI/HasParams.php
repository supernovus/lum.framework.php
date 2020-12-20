<?php

namespace Lum\CLI;

/**
 * A trait for application classes to use which provides
 * some useful wrapper methods using the Params class.
 */
trait HasParams
{
  /**
   * A method to parse the command line parameters, get the command, and
   * dispatch to a method named handle_{groupname} where {groupname} is the
   * 'name' property of the ParamGroup object that matched the command spec.
   * If the method doesn't exist, an exception will be thrown.
   *
   * If command groups were defined but none of the defined commands matched,
   * it looks for handle_no_command() instead.
   *
   * If no command groups were defined, it looks for handle_params() instead.
   *
   * The parsed parameters array will be passed to the matching method as the 
   * first argument, the Params instance will be passed as the second argument.
   * No other arguments are passed.
   * 
   * If no matching methods were found, this will throw an Exception.
   *
   * @param Params $params  The Params instance we are using.
   *
   * @return mixed  The output from the matched method.
   */
  protected function dispatch_params (Params $params)
  {
    $parsedOpts = $params->parse();
    if (!isset($parsedOpts) || !is_array($parsedOpts))
    {
      throw new Exception("Params::parse() returned invalid output");
    }

    if ($params->hasCommands())
    { // Command groups were defined. Let's look to see if a command matches.
      $command = $params->getCommand($parsedOpts);
      if ($command)
      { // A command was matched, let's find the handle_{command} method.
        $meth = "handle_$command";
        if (method_exists($this, $meth))
        {
          return $this->$meth($parsedOpts, $params);
        }
        else
        {
          throw new Exception("Missing $meth method for specified command");
        }
      }
      elseif (method_exists($this, "handle_no_command"))
      { // No Command matched the command line
        return $this->handle_no_command($parsedOpts, $params);
      }
    }
    elseif (method_exists($this, "handle_params"))
    { // A handle_params() method is used if there are no commands.
      return $this->handle_params($parsedOpts, $params);
    }
    
    // If we reached here, no methods could be found to handle the dispatch.
    throw new Exception("Could not find anything to dispatch Params to");
  }

}