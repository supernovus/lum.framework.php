<?php

namespace Lum\Controllers\Output;

trait JSON_API
{
  use JSON;

  public function json_msg ($data=[], $opts=[])
  {
    if (!_JSON_API::has($data, 'version'))
    {
      if (property_exists($this, 'api_version') && isset($this->api_version))
      {
        _JSON_API::set($data, 'version', $this->api_version);
      }
    }
    if (!_JSON_API::has($data, 'session_id'))
    {
      if (property_exists($this, 'session_id') && isset($this->session_id))
      {
        _JSON_API::set($data, 'session_id', $this->session_id);
      }
    }
    return $this->send_json($data, $opts);
  }

  public function json_ok ($data=[], $opts=[])
  {
    _JSON_API::set($data, 'success', true);
    return $this->json_msg($data, $opts);
  }

  public function json_err ($errors, $data=[], $opts=[])
  {
    if (!is_array($errors))
    {
      $errors = [$errors];
    }
    _JSON_API::set($data, 'success', false);
    _JSON_API::set($data, 'errors',  $errors);
    return $this->json_msg($data, $opts);
  }
}

class _JSON_API
{
  static function set (&$data, $pname, $pval)
  {
    if (is_array($data))
    {
      $data[$pname] = $pval;
    }
    elseif (is_object($data))
    {
      $data->$pname = $pval;
    }
  }

  static function get ($data, $pname)
  {
    if (is_array($data) && isset($data[$pname]))
    {
      return $data[$pname];
    }
    elseif (is_object($data) && isset($data->$pname))
    {
      return $data->$pname;
    }
  }
  
  static function has ($data, $pname)
  {
    if (is_array($data) && isset($data[$pname]))
      return true;
    elseif (is_object($data) && isset($data->$pname))
      return true;
    return false;
  }

}
