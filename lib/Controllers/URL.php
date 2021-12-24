<?php

namespace Lum\Controllers;

trait URL
{
  /**
   * Redirect to a URL.
   *
   * @param String $url    The URL to redirect to.
   * @param Opts   $opts   Options to pass to Lum\Plugins\Url::redirect().
   *
   */
  public function redirect ($url, $opts=array())
  {
    return \Lum\Plugins\Url::redirect($url, $opts);
  }

  /**
   * Return our site URL. 
   *
   * See Lum\Plugins\Url::site_url() for details.
   */
  public function url ($ssl=Null, $port=Null)
  {
    return \Lum\Plugins\Url::site_url($ssl, $port);
  }

  /**
   * Return our request URI. 
   *
   * See Lum\Plugins\Url::request_uri() for details.
   */
  public function request_uri ()
  {
    return \Lum\Plugins\Url::request_uri();
  }

  /**
   * Return our current URL. 
   *
   * See Lum\Plugins\Url::current_url() for details.
   */
  public function current_url ()
  {
    return \Lum\Plugins\Url::current_url();
  }

  /**
   * Send a file download to the client's browser.
   * Ends the current PHP process.
   *
   * See Lum\Plugins\Url::download() for details.
   */
  public function download ($file, $opts=[])
  {
    return \Lum\Plugins\Url::download($file, $opts);
  }

  /**
   * Is the server using a POST request?
   */
  public function is_post ()
  {
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST))
    {
      return True;
    }
    return False;
  }

}
