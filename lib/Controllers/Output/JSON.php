<?php

namespace Lum\Controllers\Output;

trait JSON
{
  /**
   * Should we enable the IE JSON output hack?
   * It means every time send_json() is used we will use user agent
   * sniffing (not really super accurate) to detect if IE is being used.
   * If it is, then we use 'text/plain' instead of 'application/json'.
   */
  protected $json_ie_support = false;

  /**
   * When using the send_json() method, this defines a method name in an object
   * that can be used to convert the object to JSON.
   */
  protected $to_json_method = 'to_json';

  /**
   * When using the send_json() method, this defines a method name in an object
   * that can be used to convert the object to a PHP array.
   *
   * This is only used if the $to_json_method method was not found.
   */
  protected $to_array_method = 'to_array';

  /** 
   * Sometimes we want to send JSON data instead of a template.
   *
   * @param mixed $data         The data to send (see below)
   * @param mixed $opts         Options, see below.
   *
   * If the $data is a PHP Array, or a scalar value, then it will be processed 
   * using the json_encode() function. 
   * In this case, there is one recognized option:
   *
   *   'fancy'     If set to True, we will use JSON_PRETTY_PRINT option.
   *
   * If the $data is an Object, then how we encode it is determined by
   * what methods it has available:
   *
   * If it has a method by the name of the $to_json_method property,
   * it will be called, and passed the $opts as it's sole parameter.
   *
   * If it has a method by the name of the $to_array_method property,
   * it will be called and passed the $opts as it's sole parameter, and
   * then the array generated will be passed to json_encode().
   *
   * If it has neither of those methods, it will be passed directly to
   * json_encode() (with only the 'fancy' option being recognized.)
   *
   * If the $data is a string, it will be assumed to be a valid JSON string,
   * and will be sent as is.
   *
   * Anything else will fail and throw an Exception.
   */
  public function send_json ($data, $opts=[])
  { 
    $core = \Lum\Core::getInstance();
    $core->output->json($this->json_ie_support);
    $core->output->nocache($this->cache_expires);

    $json_opts = 0;
    if 
    (
      isset($opts['fancy']) 
      && $opts['fancy'] 
      && defined('JSON_PRETTY_PRINT') // PHP 5.4+
    )
    {
      $json_opts = JSON_PRETTY_PRINT;
    }

    if (is_string($data))
    { // A passthrough for JSON strings.
      $json = $data;
    }
    elseif (is_array($data) || is_scalar($data)) 
    { // Encode the array/scalar as JSON and send it.
      $json = json_encode($data, $json_opts);
    }
    elseif (is_object($data))
    { // Magic for converting objects to JSON.
      $meth1 = $this->to_json_method;
      $meth2 = $this->to_array_method;
      if (is_callable([$data, $meth1]))
      {
        $json = $data->$meth1($opts);
      }
      elseif (is_callable([$data, $meth2]))
      {
        $array = $data->$meth2($opts);
        $json = json_encode($array, $json_opts);
      }
      else
      {
        $json = json_encode($data, $json_opts);
      }
    }
    else
    {
      throw new \Exception('Unsupported data type sent to send_json()');
    }
    return $json;
  }

}