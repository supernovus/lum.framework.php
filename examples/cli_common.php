<?php

require_once 'vendor/autoload.php';

use \Lum\CLI\DebugParams as DP;

abstract class TestApp extends \Lum\CLI\ParamsApp
{
  protected $usage_message = "For usage use {help}, use -H for opt info, or --debug for more debug info.";

  protected function initialize_params ($params, $opts)
  {
    $params->auto_chain = true;
    $params
      ->useHelp()
      ->toggle('H', 'Unlisted option', 'heave')
      ->command('debug')
      ->flag(true, 'debug', 'Debug info');
  }

  protected function debug_opts ($opts, $params, $prefix='')
  {
    $want = DP::PARSED | DP::JSON | DP::PRETTY;
    return $prefix . DP::getInfo($params, $want, $opts) . "\n";
  }

  protected function handle_debug ($opts, $params)
  {
    if ($opts['heave'])
    { // A short debug.
      return $this->debug_opts($opts, $params);
    }
    else
    { // A more full debugging experience.
      $lvl = $opts['debug'];
      $want = DP::JSON | DP::PRETTY | DP::PARSED | DP::GETOPTS;
      if ($lvl > 1)
      {
        $want = $want | DP::SETTINGS | DP::GROUPS | DP::WITH_VARS;
      }
      if ($lvl > 2)
      {
        $want = $want | DP::PARAMS | DP::WITH_PARAMS;
      }
      if ($lvl > 3)
      {
        $want = $want | DP::RAW;
      }
      return "handle_debug()\n" . DP::getInfo($params, $want, $opts) . "\n";
    }
  }

  protected function handle_default ($opts, $params)
  {
    if ($opts['heave'])
    { // Short debug info.
      $debug = $this->handle_debug($opts, $params);
      echo "handle_no_command()\n";
      echo $debug;
    }

    return parent::handle_default($opts, $params);
  }

}
