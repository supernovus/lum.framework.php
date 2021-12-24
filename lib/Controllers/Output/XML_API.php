<?php

namespace Lum\Controllers\Output;

trait XML_API
{
  use XML;

  public function xml_msg ($data, $opts=[])
  {
    if (!_XML_API::has($data, 'version'))
    {
      if (property_exists($this, 'api_version') && isset($this->api_version))
      {
        _XML_API::set($data, 'version', $this->api_version);
      }
    }
    if (!has_json_property($data, 'session_id'))
    {
      if (property_exists($this, 'session_id') && isset($this->session_id))
      {
        _XML_API::set($data, 'session_id', $this->session_id);
      }
    }
    return $this->send_xml($data, $opts);
  }

  public function xml_ok ($data=null, $opts=[])
  { // Boolean attribute.
    if (is_null($data))
    {
      $data = new \SimpleXMLElement('<response/>');
    }
    _XML_API::set($data, 'success', 'success');
    return $this->xml_msg($data, $opts);
  }

  public function xml_err ($errors, $data=null, $opts=[])
  {
    if (!is_array($errors))
    {
      $errors = [$errors];
    }
    if (is_null($data))
    {
      $data = new \SimpleXMLElement('<response/>');
    }
    if (_XML_API::has($data, 'success'))
    {
      _XML_API::del($data, 'success');
    }
    if ($data instanceof \DOMNode)
    { // Convert to SimpleXML.
      $data = simplexml_import_dom($data);
    }
    if ($data instanceof \SimpleXMLElement)
    {
      $errNode = $data->addChild('errors');
      foreach ($errors as $errmsg)
      {
        $errNode->addChild('error', $errmsg);
      }
    }
    else
    {
      throw new \Exception("invalid XML sent to xml_err()");
    }
    return $this->xml_msg($data, $opts);
  }
}

class _XML_API
{
  static function set ($data, $pname, $pval)
  {
    if ($data instanceof \SimpleXMLElement)
    {
      $data[$pname] = $pval;
    }
    elseif ($data instanceof \DOMElement)
    {
      $data->setAttribute($pname, $pval);
    }
    elseif ($data instanceof \DOMDocument)
    {
      $data->documentElement->setAttribute($pname, $pval);
    }
    else
    {
      throw new \Exception("Invalid XML sent to _XML_API::set()");
    }
  }
  
  static function get ($data, $pname)
  {
    if ($data instanceof \SimpleXMLElement)
    {
      return (string)$data[$pname];
    }
    elseif ($data instanceof \DOMElement)
    {
      return $data->getAttribute($pname);
    }
    elseif ($data instanceof \DOMDocument)
    {
      return $data->documentElement->getAttribute($pname);
    }
    else
    {
      throw new \Exception("Invalid XML sent to _XML_API::get()");
    }
  }
  
  static function has ($data, $pname)
  {
    if ($data instanceof \SimpleXMLElement)
    {
      return isset($data[$pname]);
    }
    elseif ($data instanceof \DOMElement)
    {
      return $data->hasAttribute($pname);
    }
    elseif ($data instanceof \DOMDocument)
    {
      return $data->documentElement->hasAttribute($pname);
    }
    else
    {
      throw new \Exception("Invalid XML sent to _XML_API::has()");
    }
  }
  
  static function del ($data, $pname)
  {
    if ($data instanceof \SimpleXMLElement)
    {
      unset($data[$pname]);
    }
    elseif ($data instanceof \DOMElement)
    {
      $data->removeAttribute($pname);
    }
    elseif ($data instanceof \DOMDocument)
    {
      $data->documentElement->removeAttribute($pname);
    }
    else
    {
      throw new \Exception("Invalid XML sent to _XML_API::del()");
    }
  }

}
