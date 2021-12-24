<?php

namespace Lum\Controllers;

trait Wrappers
{
  /**
   * Add a wrapper to a controller method that you can call from
   * a view template (as a Callable.)
   *
   * As an example, a call of $this->addWrapper('url'); will create
   * a callable called $url, that when called, will make a call to $this->url()
   * with the specified parameters.
   *
   * @param string $method   The method we want to wrap into a closure.
   *
   * @param string $varname  (Optional) The name of the callable for the views.
   *
   * If $varname is not specified, or null, it will be the same as the $method.
   *
   * @param bool   $closure  (Default false) If true, use a Closure.
   *
   * A Closure can be used in a few circumstances where a Callable is not
   * going to work. If you don't think you need it, don't use it.
   *
   * @return mixed  Either the Callable or Closure that was created.
   */
  protected function addWrapper ($method, $varname=Null, $closure=False)
  {
    if (is_null($varname))
      $varname = $method;
    if ($closure)
    { // A closure may be desired in some cases.
      $that = $this; // A wrapper to ourself.
      $closure = function () use ($that, $method)
      {
        $args = func_get_args();
        $meth = [$that, $method];
        return call_user_func_array($meth, $args);
      };
      $this->data[$varname] = $closure;
      return $closure;
    }
    else
    { // Callables are simpler than closures in most cases.
      $callable = [$this, $method];
      $this->data[$varname] = $callable;
      return $callable;
    }
  }

}
