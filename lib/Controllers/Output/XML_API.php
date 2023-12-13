<?php

namespace Lum\Controllers\Output;

use DOMNode,DOMElement,DOMDocument,SimpleXMLElement,SimpleDOM;

trait XML_API
{
  use XML, API;

  public function xml_msg ($data, $opts=[])
  {
    $this->api_set_msg(_XML_API::class, $data);
    return $this->send_xml($data, $opts);
  }

  public function xml_ok ($data=null, $opts=[])
  { // Boolean attribute.
    if (is_null($data))
    {
      $data = _XML_API::response();
    }

    $this->api_set_ok(_XML_API::class, $data, true);

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
      $data = _XML_API::response(true);
    }

    $this->api_set_ok(_XML_API::class, $data, false);

    if (!($data instanceof SimpleDOM))
    { // Convert it to a SimpleDOM object.
      $data = SimpleDOM::load($data);
      if (!isset($data))
      {
        throw new APIException(INVALID_TYPE);
      }
    }
    
    $errNode = $data->addChild($this->api_errname());
    $errName = $this->api_var('api_error_child_data_name', 'error');

    foreach ($errors as $error)
    {
      if ($error instanceof DOMNode || $error instanceof SimpleXMLElement)
      { // Append DOM/SimpleXML objects directly.
        $errNode->appendChild($error);
      }
      else
      { // Anything else, assume it is a string, or stringifiable object.
        $errNode->addChild($errName, $error);
      }
    }

    return $this->xml_msg($data, $opts);
  }
}

class _XML_API
{
  // A default element if nothing valid was passed.
  static function response($sdom=false)
  {
    $xml = '<response/>';
    return $sdom ? new SimpleDOM($xml) : new SimpleXMLElement($xml);
  }

  static function set ($data, $pname, $pval)
  {
    if ($pval === false)
    { // Unset false attributes.
      return static::del($data, $pname);
    }
    elseif ($pval === true)
    { // Boolean true attributes the value is the name.
      $pval = $pname;
    }

    if ($data instanceof SimpleXMLElement)
    {
      $data[$pname] = $pval;
    }
    elseif ($data instanceof DOMElement)
    {
      $data->setAttribute($pname, $pval);
    }
    elseif ($data instanceof DOMDocument)
    {
      $data->documentElement->setAttribute($pname, $pval);
    }
    else
    {
      throw new APIException(INVALID_TYPE);
    }
  }
  
  static function get ($data, $pname)
  {
    if ($data instanceof SimpleXMLElement)
    {
      return (string)$data[$pname];
    }
    elseif ($data instanceof DOMElement)
    {
      return $data->getAttribute($pname);
    }
    elseif ($data instanceof DOMDocument)
    {
      return $data->documentElement->getAttribute($pname);
    }
    else
    {
      throw new APIException(INVALID_TYPE);
    }
  }
  
  static function has ($data, $pname)
  {
    if ($data instanceof SimpleXMLElement)
    {
      return isset($data[$pname]);
    }
    elseif ($data instanceof DOMElement)
    {
      return $data->hasAttribute($pname);
    }
    elseif ($data instanceof DOMDocument)
    {
      return $data->documentElement->hasAttribute($pname);
    }

    return false;
  }
  
  static function del ($data, $pname)
  {
    if ($data instanceof SimpleXMLElement)
    {
      unset($data[$pname]);
    }
    elseif ($data instanceof DOMElement)
    {
      $data->removeAttribute($pname);
    }
    elseif ($data instanceof DOMDocument)
    {
      $data->documentElement->removeAttribute($pname);
    }
    else
    {
      throw new APIException(INVALID_TYPE);
    }
  }

}
