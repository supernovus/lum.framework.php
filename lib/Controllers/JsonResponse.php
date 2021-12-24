<?php

// DEPRECATED

namespace Lum\Controllers;

use \Lum\Controllers\Output\{JSON_API, _JSON_API};

function set_json_property (&$data, $pname, $pval)
{
  return _JSON_API::set($data, $pname, $pval);
}

function get_json_property ($data, $pname)
{
  return _JSON_API::get($data, $pname)
}

function has_json_property ($data, $pname)
{
  return _JSON_API::has($data, $pname);
}

/**
 * @deprecated This was moved to Output\JSON_API
 */
trait JsonResponse
{
  use JSON_API;
}