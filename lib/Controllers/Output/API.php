<?php

namespace Lum\Controllers\Output;

const INVALID_TYPE = "Invalid data type";

trait API
{
  protected function api_var(string $prop, string $default)
  {
    if (property_exists($this, $prop) && is_string($this->$prop))
    {
      return $this->$prop;
    }

    return $default;
  }

  protected function api_set_if(
    &$data, 
    string $dprop, 
    string $cprop, 
    string $class)
  {
    if (!$class::has($data, $dprop))
    {
      if (property_exists($this, $cprop) && isset($this->$cprop))
      {
        $class::set($data, $dprop, $this->$cprop);
      }
    }
  }

  protected function api_set_msg(string $class, &$data)
  {
    $ver = $this->api_var('api_version_data_name', 'version');
    $sid = $this->api_var('api_session_data_name', 'session_id');

    $this->api_set_if($data, $ver, 'api_version', $class);
    $this->api_set_if($data, $sid, 'session_id',  $class);
  }

  protected function api_set_ok(string $class, &$data, bool $value)
  {
    $ok = $this->api_var('api_ok_property', 'success');
    $class::set($data, $ok, $value);
  }

  protected function api_errname()
  {
    return $this->api_var('api_errors_data_name', 'errors');
  }
}

class APIException extends \Exception {}
