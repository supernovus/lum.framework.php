<?php

// DEPRECATED

namespace Lum\Controllers;

use \Lum\Controllers\Output\{XML_API, _XML_API};

function set_xml_attr ($data, $pname, $pval)
{
  return _XML_API::set($data, $pname, $pval);
}

function get_xml_attr ($data, $pname)
{
  return _XML_API::get($data, $pname);
}

function has_xml_attr ($data, $pname)
{
  return _XML_API::has($data, $pname);
}

function del_xml_attr ($data, $pname)
{
  _XML_API::del($data, $pname);
}

/**
 * @deprecated This was moved to Output\XML_API
 */
trait XMLResponse
{
  use XML_API;
}
