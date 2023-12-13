<?php

namespace Lum\Controllers\Output;

trait JSON_API
{
  use JSON, API;

  public function json_msg ($data=[], $opts=[])
  {
    $this->api_set_msg(_JSON_API::class, $data);
    return $this->send_json($data, $opts);
  }

  public function json_ok ($data=[], $opts=[])
  {
    $this->api_set_ok(_JSON_API::class, $data, true);
    return $this->json_msg($data, $opts);
  }

  public function json_err ($errors, $data=[], $opts=[])
  {
    if (!is_array($errors))
    {
      $errors = [$errors];
    }

    $this->api_set_ok(_JSON_API::class, $data, false);
    _JSON_API::set($data, $this->api_errname(), $errors);

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
    else
    {
      throw new APIException(INVALID_TYPE);
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
    else
    {
      throw new APIException(INVALID_TYPE);
    }
  }
  
  static function has ($data, $pname)
  {
    if (is_array($data) && isset($data[$pname]))
    {
      return true;
    }
    elseif (is_object($data) && isset($data->$pname))
    {
      return true;
    }
    
    return false;
  }

  static function del (&$data, $pname)
  {
    if (is_array($data))
    {
      unset($data[$pname]);
    }
    elseif (is_object($pname))
    {
      unset($data->$pname);
    }
    else
    {
      throw new APIException(INVALID_TYPE);
    }
  }

}
