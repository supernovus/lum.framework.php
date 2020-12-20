<?php

namespace Lum\CLI;

/**
 * A trait for application classes to use which provides
 * a default implementation of the 'help', 'usage', and 'help_usage' commands.
 */
trait HasHelp
{
  /**
   * A default handler for the 'usage' command.
   *
   * Returns the output from the $params->getUsage() method.
   */
  protected function handle_usage ($opts, $params)
  {
    return $params->getUsage();
  }

  /**
   * A default handler for the 'help' command.
   *
   * Returns the output from the $params->getHelp() method.
   */
  protected function handle_help ($opts, $params)
  {
    $topic = $opts[$params->help_standalone];
    return $params->getHelp($topic);
  }

  /**
   * A default handler for the 'help_usage' command.
   *
   * Depending on the value of the $params->help_usage_combo parameter,
   * this will either return $params->getUsage() or $params->getHelp().
   */
  protected function handle_help_usage ($opts, $params)
  {
    $help = $params->help_usage_combo;
    if (isset($opts[$help]) && is_string($opts[$help]))
    { // An argument was passed to the help command.
      $topic = $opts[$help];
      return $params->getHelp($topic);
    }
    else
    { // No argument, show the usage summary instead.
      return $params->getUsage();
    }
  }

}