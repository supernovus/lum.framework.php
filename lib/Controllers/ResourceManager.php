<?php

namespace Lum\Controllers;

use Exception;

/**
 * Adds Resource Management for CSS, Stylesheets, etc.
 */
trait ResourceManager
{
  /**
   * If this is true, we print a warning to the error log if a requested
   * resource file cannot be found.
   */
  public $warn_on_missing_resources = true;

  /**
   * If this is set to true, we will add a Link: header for each resource
   * loaded so that in HTTP/2 they will be streamed as a part of the request.
   */
  public $use_link_header = false;

  /**
   * If using $use_link_header, this can be defined as a prefix to add
   * to all of the links added. Useful if you have multiple apps on a URL
   * and need to make sure the right subdirectory is used.
   */
  public $link_header_prefix;

  /**
   * Definitions for each type of resource that we can load.
   */
  protected $resource_types =
  [
    'js' =>
    [
      'as'    => 'script',
      'name'  => 'scripts',
      'exts'  => ['.min.js', '.js'],
      'path'  => [],
    ],
    'css' =>
    [
      'as'    => 'style',
      'name'  => 'stylesheets',
      'exts'  => ['.min.css', '.css'],
      'path'  => [],
    ],
    'font' =>
    [
      'as'    => 'font',
      'name'  => 'fonts',
      'exts'  => ['.woff2','.woff', '.otf', '.ttf'],
      'path'  => [],
    ],
  ];

  /**
   * Definitions for resource groups.
   * 
   * A group for one resource type MAY in fact depend on a resource of another
   * type. For instance, you may have a Javascript file that depends on a
   * CSS stylesheet being loaded. You can define a rule that will include it,
   * by using a 'type:name' format, such as 'css:foobar'.
   */
  protected $resource_groups =
  [
    'js'   => [],
    'css'  => [],
    'font' => [],
  ];

  /**
   * A list of loaded resources.
   * 
   * @var array
   */
  protected $resources_added =
  [
    'js'   => [],
    'css'  => [],
    'font' => [],
  ];

  protected function get_resource_def ($type)
  {
    $def = [];

    if (property_exists($this, 'resources') && isset($this->resources[$type]))
    { // Original property name, sourced first.
      $def += $this->resources[$type];
    }

    if (isset($this->resource_types[$type]))
    {
      $def += $this->resource_types[$type];
    }

    if (isset($this->resource_groups[$type]))
    {
      $rgrp = $this->resource_groups[$type];
      if (isset($def['groups']))
      {
        $egrp = $def['groups'];
        $def['groups'] = array_merge($egrp, $rgrp);
      }
      else
      {
        $def['groups'] = $rgrp;
      }
    }

    return (count($def) > 0 ? $def : null);
  }

  /**
   * Find a resource file, based on known paths and extensions.
   *
   * @param string $type    The resource type.
   * @param string $name    The resource name without path or extension.
   */
  public function find_resource ($type, $name)
  {
    $resdef = $this->get_resource_def($type);
    if (!isset($resdef)) return null;

    $exts = $resdef['exts'];
    $path = $resdef['path'];

    foreach ($path as $dir)
    {
      foreach ($exts as $ext)
      {
        $filename = $dir . '/' . $name . $ext;
        if (file_exists($filename))
        {
          return $filename;
        }
      }
    }

    return null;
  }

  /**
   * Add a resource file to an array of resources for use in view templates.
   *
   * @param string  $type    The resource type.
   * @param string  $name    The resource or group name.
   * @param boolean $block   Don't actually add it, make it un-addable.
   */
  public function use_resource ($type, $name, $block=false): bool
  {
    $resdef = $this->get_resource_def($type);
    if (!isset($resdef)) return false;

    // Handle array input.
    if (is_array($name))
    {
      foreach ($name as $res)
      {
        $this->use_resource($type, $res, $block);
      }
      return true; // All done.
    }

    if (isset($this->resources_added[$type][$name]))
    {
      return true;
    }

    // If this is a group, we process the group members.
    if (isset($resdef['groups'][$name]))
    {
      $group = $resdef['groups'][$name];
      foreach ($group as $res)
      {
        $resblock = $block;

        if (substr($res, 0, 1) === '!')
        {
          $res = substr($res, 1);
          $resblock = true;
        }

        if (strpos($res, ':') === false)
        {
          $this->use_resource($type, $res, $resblock);
        }
        else
        {
          $parts = explode(':', $res);
          $etype = $parts[0];
          $ename = $parts[1];
          $this->use_resource($etype, $ename, $resblock);
        }
      }
      $this->resources_added[$type][$name] = $name;
      return true; // We've imported the group, let's leave now.
    }

    if ($block)
    {
      $this->resources_added[$type][$name] = true;
      return true;
    }

    $isURL = false;
    if (isset($resdef['urls'][$name]))
    {
      $isURL = true;
      $file = $resdef['urls'][$name];
    }
    else
    {
      $file = $this->find_resource($type, $name);
      if (!isset($file))
      {
        if ($this->warn_on_missing_resources)
          error_log("Could not find $type file for: '$name'.");
        return false;
      }

      if ($this->use_link_header)
      {
        $prefix = $this->link_header_prefix;
        $astype = $resdef['as'];
        header("Link: <{$prefix}{$file}>; rel=preload; as=$astype", false);
      }
    }

    $resname = $resdef['name'];

#    error_log("Adding $type '$name' to $resname as $file");

    if (!isset($this->data[$resname]))
    {
      $this->data[$resname] = [];
    }

    if ($isURL)
      $this->data[$resname][] = ['url'=>$file];
    else
      $this->data[$resname][] = $file;

    $this->resources_added[$type][$name] = $file;
    return true;
  }

  /**
   * Reset a resource group.
   */
  public function reset_resource ($type)
  {
    $resdef = $this->get_resource_def($type);
    if (!isset($resdef)) return;

    $resname = $resdef['name'];
    unset($this->data[$resname]);
    $this->resources_added[$type] = [];
  }

  /**
   * Add a Javascript file or group to our used resources.
   */
  public function add_js ($name)
  {
    return $this->use_resource('js', $name);
  }

  /**
   * Add a CSS stylesheet file or group to our used resources.
   */
  public function add_css ($name)
  {
    return $this->use_resource('css', $name);
  }

  /**
   * Block a Javascript file or group from being loaded.
   * This must be done BEFORE any other calls to add_js() because if the
   * resource is already added, it cannot be blocked.
   */
  public function block_js ($name)
  {
    return $this->use_resource('js', $name, true);
  }

  /**
   * Block a CSS stylesheet. The same rules apply with this as with JS.
   */
  public function block_css ($name)
  {
    return $this->use_resource('css', $name, true);
  }

  /**
   * Add resource paths.
   * 
   * @param string $type - The resource type we're adding to.
   * @param string|array $paths - One or more paths to add.
   * @param int $offset (Optional, default: 1) Offset to add paths at.
   * @param int $remove (Optional, default: 0) Remove this many items.
   * 
   * If this is set to `-1` then we will replace ALL existing paths.
   * 
   * @return void
   */
  public function add_resource_paths (
    string $type, 
    string|array $paths, 
    int $offset=1, 
    int $remove=0
  )
  {
    if (property_exists($this, 'resources') 
      && isset($this->resources[$type], $this->resources[$type]['path']))
    { // Legacy resource property found.
      $prop = 'resources';
    }
    elseif (isset($this->resource_types[$type]))
    { // Modern, modular resources in use.
      if (!isset($this->resource_types[$type]['path']))
      { // Add a path property.
        $this->resource_types[$type]['path'] = [];
      }
      $prop = 'resource_types';
    }
    else
    {
      throw new \Exception("Invalid resource type");
    }

    if ($remove === -1)
    { // Replace ALL existing paths.
      $this->$prop[$type]['path'] = $paths;
    }
    else
    {
      array_splice($this->$prop[$type]['path'], $offset, $remove, $paths);
    }
  }

  /**
   * Load a resource configuration.
   * 
   * @param string|array $config - A Lum config name, or actual config data.
   * 
   * TODO: document the supported parameters.
   * 
   * @param null|string $type (Optional) A specific config type to load.
   * 
   * If NOT specified, every key in the $config will be assumed to be the name
   * of a resource type, and the value must be the config settings to add.
   * 
   * @return void 
   * @throws Exception An invalid Lum config name was specified.
   */
  public function load_resource_config (
    string|array $config, 
    ?string $type=null
  )
  {
    if (is_string($config))
    {
      $core = \Lum\Core::getInstance();
      $cname = $config;
      $config = $core->conf[$cname];
      if (!is_array($config))
      {
        throw new \Exception("Invalid config '$cname'");
      }
    }

    if (isset($type))
    {
      if (isset($config[$type]))
        $config = $config[$type];
      $this->update_resource_config($type, $config);
    }
    else
    {
      foreach ($config as $type => $subconf)
      {
        $this->update_resource_config($type, $subconf);
      }
    }
  }

  protected function update_resource_config (string $type, array $config)
  {
    if (isset($config['path']))
    { // Add to or replace the existing path.
      $offset = isset($config['pathOffset'])  
        ? $config['pathOffset'] 
        : 0;

      $remove = isset($config['pathReplace']) 
        ? $config['pathReplace'] 
        : ($offset ? 0 : -1);
      
      $this->add_resource_paths($type, $config['path'], $offset, $remove);
    }

    if (isset($config['groups']))
    {
      $cgrp = $config['groups'];
      if (isset($this->resource_groups[$type]))
      {
        $egrp = $this->resource_groups[$type];
        $this->resource_groups[$type] = array_merge($egrp, $cgrp);
      }
      else
      {
        $this->resource_groups[$type] = $cgrp;
      }
    }
  }

}
