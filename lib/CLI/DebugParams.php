<?php

namespace Lum\CLI;

/**
 * A simple object for debugging Params.
 */
class DebugParams
{
  const PARSED       = 1;
  const RAW          = 2;
  const GETOPTS      = 4;
  const SETTINGS     = 8;
  const PARAMS       = 16;
  const GROUPS       = 32;
  const WITH_VARS    = 64;
  const WITH_PARAMS  = 128;
  const ALL          = 255;

  const JSON         = 256;
  const PRETTY       = 512;
  const ALL_JSON     = 1023;

  public static function getInfo (Params $params, $get=false, $opts=null)
  {
    if ($get === true)
    { // Boolean true is the same as ALL_JSON
      $get = self::ALL_JSON;
    }
    elseif ($get === false)
    { // Boolean false is the same as ALL
      $get = self::ALL;
    }
    elseif (!is_int($get))
    { // Not a valid type.
      throw new Exception("Debug flags must be an integer, true, or false");
    }

    if ($get < self::PARSED || $get > self::ALL_JSON)
    { // Out of valid bounds.
      throw new Exception("Invalid debug flags '$get' specified.");
    }

    $info = [];

    if ($get & self::PARSED)
    {
      if (!is_array($opts))
      {
        $opts = $params->parse();
      }
      $info['parsedOpts'] = $opts;
    }
    if ($get & self::RAW)
    {
      $optind = null;
      $info['rawOpts'] = $params->_getopts(false, $optind);
      $info['optIndex'] = $optind;
    }
    if ($get & self::GETOPTS)
    {
      $info['getOpts'] = $params->_getopts(true);
    }
    if ($get & self::SETTINGS)
    {
      $info['paramsSettings'] = $params;
    }
    if ($get & self::PARAMS)
    {
      $info['allParams'] = $params->getParams();
    }
    if ($get & self::GROUPS)
    {
      $info['paramGroups'] = $params->getGroups();

      if ($get & self::WITH_VARS)
      {
        $gvars = [];
        foreach ($info['paramGroups'] as $name => $group)
        {
          $gvars[$name] = $group->getVars();
        }
        $info['groupVars'] = $gvars;
      }
      if ($get & self::WITH_PARAMS)
      {
        $gparams = [];
        foreach ($info['paramGroups'] as $name => $group)
        {
          $gparams[$name] = $group->getParams();
        }
        $info['groupParams'] = $gparams;
      }
    }

    if ($get & self::JSON)
    { // We want JSON.
      $jflags = ($get & self::PRETTY) ? JSON_PRETTY_PRINT : 0;
      return json_encode($info, $jflags);
    }
    else
    { // Not JSON, return the info structure.
      return $info;
    }
  }
}