<?php

namespace Lum\Controllers\Output;

use Exception;

trait XML
{
  /**
   * Should we use text/xml instead of application/xml?
   */
  protected $use_xml_text = false;

  /**
   * When using the send_xml() method, this defines a method name in an object
   * that can be used to convert the object to an XML string.
   */
  protected $to_xml_method  = 'to_xml';

  /** 
   * Sometimes we want to send XML data instead of a template.
   *
   * @param mixed $data        The data to send (see below)
   * @param mixed $opts        Options, used in some cases (see below)
   *
   * If $data is a string, we assume it's valid XML and pass it through.
   *
   * If $data is a SimpleXML Element, DOMDocument or DOMElement, we use the
   * native calls of those libraries to convert it to an XML string.
   * One caveat: we assume that DOMElement objects have an ownerDocument,
   * and if they don't, this method will fail.
   *
   * If the $data is another kind of object, and has a method as per the
   * $this->to_xml_method (default: 'to_xml') then it will be called with
   * the $opts as its first parameter.
   *
   * Anything else will throw an Exception.
   */
  public function send_xml ($data, $opts=[])
  {
    $core = \Lum\Core::getInstance();
    $core->output->xml($this->use_xml_text);
    $core->output->nocache($this->cache_expires);

    $fancy = isset($opts['fancy']) 
           ? (bool)$opts['fancy'] 
           : (isset($opts['reformat'])
           ? (bool)$opts['reformat']
           : false);

    if (is_string($data))
    { // Passthrough.
      $xml = $data;
    }
    elseif (is_object($data))
    {
      $method = $this->to_xml_method;

      if ($data instanceof \SimpleXMLElement)
        $xml = $data->asXML();
      elseif ($data instanceof \DOMDocument)
        $xml = $data->saveXML();
      elseif ($data instanceof \DOMElement)
        $xml = $data->ownerDocument->saveXML();
      elseif (is_callable([$data, $method]))
      {
        $xml = $data->$method($opts);
        $fancy = false; // the method must do the formatting.
      }
      else
        throw new Exception('Unsupported object sent to send_xml()');
    }
    else
    {
      throw new Exception('Unsupported data type sent to send_xml()');
    }

    if ($fancy)
    { // Reformat the output.
      $dom = new \DOMDocument('1.0');
      $dom->preserveWhiteSpace = false;
      $dom->formatOutput = true;
      $dom->loadXML($xml);
      $xml = $dom->saveXML();
    }

    return $xml;
  }

}