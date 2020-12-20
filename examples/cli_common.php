<?php

require_once 'vendor/autoload.php';

function jp ($what)
{
  return json_encode($what, JSON_PRETTY_PRINT);
}

function wrap ($name, $what)
{
  return "<$name>\n".jp($what)."\n</$name>\n";
}

abstract class TestApp extends \Lum\CLI\ParamsApp
{
  protected $usage_message = "For usage use {help}, use -H for opt info, or --debug for full debug info.";

  protected function initialize_params ($params, $opts)
  {
    $params->auto_chain = true;
    $params
      ->useHelp()
      ->toggle('H', 'Unlisted option', 'heave')
      ->command('debug')
      ->toggle(true, 'debug', 'Debug info');
  }

  protected function handle_debug ($opts, $params)
  {
    $out  = $opts['heave'] ? '' : "handle_debug()\n";
    if (!$opts['heave'])
    {
      $out .= wrap('paramDefs', $params->getParams());
      $out .= wrap('params', $params);
      $out .= wrap('getopts', $params->_getopts(true));
    }
    $out .= wrap('opts', $opts);
    return $out;
  }

  protected function handle_no_command ($opts, $params)
  {
    if ($opts['heave'])
    { // Short debug info.
      $debug = $this->handle_debug($opts, $params);
      echo "handle_no_command()\n";
      echo $debug;
    }

    return parent::handle_no_command($opts, $params);
  }

}
