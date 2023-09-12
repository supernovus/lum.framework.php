<?php

namespace Lum\Controllers;

use Lum\Web\Url as LWU;

trait URL
{
  /**
   * Redirect to a URL.
   *
   * @param String $url    The URL to redirect to.
   * @param Opts   $opts   Options to pass to Lum\Plugins\Url::redirect().
   *
   */
  public function redirect ($url, $opts=array()): never
  {
    LWU::redirect($url, $opts);
  }

  /**
   * Return our site URL. 
   *
   * See Lum\Plugins\Url::site_url() for details.
   */
  public function url ($ssl=Null, $port=Null): string
  {
    return LWU::site_url($ssl, $port);
  }

  /**
   * Return our request URI. 
   *
   * See Lum\Plugins\Url::request_uri() for details.
   */
  public function request_uri (): string
  {
    return LWU::request_uri();
  }

  /**
   * Return our current URL. 
   *
   * See Lum\Plugins\Url::current_url() for details.
   */
  public function current_url (): string
  {
    return LWU::current_url();
  }

  /**
   * Send a file download to the client's browser.
   * Ends the current PHP process.
   *
   * See Lum\Plugins\Url::download() for details.
   */
  public function download ($file, $opts=[]): never
  {
    LWU::download($file, $opts);
  }

  /**
   * Is the server using a POST request?
   */
  public function is_post (): bool
  {
    return ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST));
  }

}
