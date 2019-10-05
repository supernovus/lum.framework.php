<?php

namespace Lum\Controllers\Auth;

class Token extends Plugin
{
  public function options ($conf)
  {
    return ['header','model'];
  }

  public function getAuth ($conf, $opts=[])
  {
    $context = isset($opts['context']) ? $opts['context'] : null;
    $header  = isset($opts['header'])  ? $opts['header']  : 'X-Lum-Auth-Token';
    $model   = isset($opts['model'])   ? $opts['model']   : 'auth_tokens';

    if (!isset($model))
    {
      error_log("No model set, cannot continue.");
      return false;
    }

    if (is_string($model))
    {
      $model = $this->parent->model($model);
    }

    $headers = \getallheaders();
    if (isset($headers[$header]))
    {
#      error_log("Token header found, parsing.");
      $header = $headers[$header];
      $user = $model->getUser($header);
      if (isset($user))
      {
#        error_log("Token validated.");
        $this->parent->set_user($user, false);
        return true;
      }
      elseif (isset($model->errors))
      {
#        error_log("Token did not validate, returning errors.");
        foreach ($model->errors as $error)
        {
          $this->parent->auth_errors[] = $error;
        }
        return false;
      }
    }
  }
}
