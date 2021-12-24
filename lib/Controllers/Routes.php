<?php

namespace Lum\Controllers;

trait Routes
{
  /**
   * Does a route exist?
   */
  public function has_route ($route)
  {
    $core = \Lum\Core::getInstance();
    return $core->router->has($route);
  }

  /**
   * Go to a route.
   *
   * @param string $page  The name of the route we want to go to.
   * @param array $params (Optional) Passed to Router::go().
   * @param array $opts   (Optional) Passed to Router::go().
   *
   * One extra option is recognized:
   *
   *  'sub'  (bool)  If true, we look for a route called '{ctrl}_{page}'
   *                 intead of just '{page}'. Where {ctrl} is the name of
   *                 the current controller, see name().
   *
   * @throws Exception  If the route does not exist.
   */
  public function go ($page, $params=[], $opts=[])
  {
    $core = \Lum\Core::getInstance();

    if (isset($opts['sub']) && $opts['sub'])
    {
      $page = $this->__classid . '_' . $page;
      unset($opts['sub']);
    }

    if ($core->router->has($page))
    {
      $core->router->go($page, $params, $opts);
    }
    else
    {
      throw new Exception("invalid target '$page' for Controller::go()");
    }
  }

  /**
   * Get a route URI.
   *
   * @param string $page  The name of the route, passed to Router::build().
   * @param array $params (Optional) Passed to Router::build().
   *
   * @return string  The route URI.
   *
   * @throws Exception  If the route does not exist.
   */
  public function get_uri ($page, $params=[])
  {
#    error_log("get_uri('$page',".json_encode($params).")");
    $core = \Lum\Core::getInstance();
    if ($core->router->has($page))
    {
      return $core->router->build($page, $params);
    }
    else
    {
      throw new Exception("invalid target '$page' for Controller::get_uri()");
    }
  }

  /**
   * Get a sub-URI of this controller.
   *
   * @param (string|null) $subpage  If a string, we look for a route
   *                                named '{ctrl}_{subpage}' where {ctrl}
   *                                is the name of the controller, see name().
   *                                If it is null, we look for a route named
   *                                {ctrl}, also see name().
   *
   * @param array $params  (Optional) Passed to get_uri().
   *
   * @return string  The route URI.
   *
   * @throws Exception  If the route does not exist.
   */
  public function get_suburi ($subpage, $params=[])
  {
    $ourid = $this->__classid;
    if (is_null($subpage))
      return $this->get_uri($ourid, $params);
    else
      return $this->get_uri($ourid.'_'.$subpage, $params);
  }

}
