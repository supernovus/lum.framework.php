<?php

namespace Lum\Controllers;

/**
 * Adds Resource Management for CSS, Stylesheets, etc.
 * Has built-in support for the Lum.js project.
 *
 * TODO: refactor this to no longer have the pre-defined resource groups.
 * Instead we'll load the resource groups from a configuration file.
 */
trait Resources
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
   * Resources represent external files, such as scripts, stylesheets, etc.
   * They are managed through a generic system that allows for easy future
   * expansion.
   *
   * Each group as a resource id, such as 'js', 'css', etc, and the following
   * definitions:
   *
   *   name:      The variable name for templates.
   *   path:      An array of paths to look for resource files in.
   *   exts:      An array of extensions to look for resource files.
   *   groups:    An array of arrays, each being named groups.
   *              It is recommended to prefix groups with an identifier
   *              such as '#'.
   *              We include '#common' and '#webcore' as example groups,
   *              they depend upon the Lum.js library set.
   *   added:     An empty array, will be populated by use_resource();
   *
   *  A group for one resource type MAY in fact depend on a resource of another
   *  type. For instance, you may have a Javascript file that depends on a
   *  CSS stylesheet being loaded. You can define a rule that will include it,
   *  by using a 'type:name' format, such as 'css:foobar'.
   *
   */
  protected $resources =
  [
    'js' =>
    [
      'as'   => 'script',
      'name' => 'scripts',
      'path' => ['scripts', 'scripts/nano', 'scripts/ext'],
      'exts' => ['.min.js', '.js'],
      'groups' =>
      [ 
        '#common' =>
        [ // The base scripts we expect everywhere.
          'jquery', 
          'core',
          'helpers',
          'hash',
          'json.jq', 
          'disabled.jq',
          'exists.jq',
        ],
        '#webcore' =>
        [ // A simplified web app model core. No rendering engine specified.
          '#common',
          'observable',
          'modelapi',
          'viewcontroller',
        ],
        '#webcore_ws' =>
        [ // A version of web core for working with web services.
          '#webcore',
          'webservice',
          'promise',
          'oquery',
          'modelapi/ws_model',
        ],
        '#base64' =>
        [ // Base64 encoding and decoding.
          'crypto/components/core-min',
          'crypto/components/enc-base64-min',
        ],
        '#ace' =>
        [ // The Ace editor, it uses an embedded module loader for components.
          'ace/src-min-noconflict/ace'
        ],
        '#editor' => 
        [ // The Lum.Editor component.
          '#common',
          '#ace', 
          '#base64',
          'editor',
        ],
      ],
      'urls' => 
      [
        '@google_charts' => 'https://www.gstatic.com/charts/loader.js',
      ],
      'added' => [], 
    ],
    'css' =>
    [
      'as'     => 'style',
      'name'   => 'stylesheets',
      'path'   => ['style'],
      'exts'   => ['.min.css', '.css'],
      'groups' => [],
      'added'  => [],
    ],
  ];

  /**
   * Find a resource file, based on known paths and extensions.
   *
   * @param String $type    The resource type.
   * @param String $name    The resource name without path or extension.
   */
  public function find_resource ($type, $name)
  {
    if (!isset($this->resources[$type]))
      return Null; 

    $exts = $this->resources[$type]['exts'];
    $path = $this->resources[$type]['path'];

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
  }

  /**
   * Add a resource file to an array of resources for use in view templates.
   *
   * @param string  $type    The resource type.
   * @param string  $name    The resource or group name.
   * @param boolean $block   Don't actually add it, make it un-addable.
   */
  public function use_resource ($type, $name, $block=false)
  {
    // Make sure it's a valid resource type.
    if (!isset($this->resources[$type])) return False;

    // Handle array input.
    if (is_array($name))
    {
      foreach ($name as $res)
      {
        $this->use_resource($type, $res, $block);
      }
      return True; // All done.
    }

    if (isset($this->resources[$type]['added'][$name]))
    {
      return True;
    }

    // If this is a group, we process the group members.
    if (isset($this->resources[$type]['groups'][$name]))
    {
      $group = $this->resources[$type]['groups'][$name];
      foreach ($group as $res)
      {
        $resblock = $block;
        if (substr($res, 0, 1) === '!')
        {
          $res = substr($res, 1);
          $resblock = true;
        }
        if (strpos($res, ':') === False)
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
      $this->resources[$type]['added'][$name] = $name;
      return True; // We've imported the group, let's leave now.
    }

    if ($block)
    {
      $this->resources[$type]['added'][$name] = true;
      return true;
    }

    $isURL = false;
    if (isset($this->resources[$type]['urls'][$name]))
    {
      $isURL = true;
      $file = $this->resources[$type]['urls'][$name];
    }
    else
    {
      $file = $this->find_resource($type, $name);
      if (!isset($file))
      {
        if ($this->warn_on_missing_resources)
          error_log("Could not find $type file for: '$name'.");
        return False;
      }
      if ($this->use_link_header)
      {
        $prefix = $this->link_header_prefix;
        $astype = $this->resources[$type]['as'];
        header("Link: <${prefix}${file}>; rel=preload; as=$astype", false);
      }
    }

    $resname = $this->resources[$type]['name'];

#    error_log("Adding $type '$name' to $resname as $file");

    if (!isset($this->data[$resname]))
    {
      $this->data[$resname] = array();
    }

    if ($isURL)
      $this->data[$resname][] = ['url'=>$file];
    else
      $this->data[$resname][] = $file;

    $this->resources[$type]['added'][$name] = $file;
    return True;
  }

  /**
   * Reset a resource group.
   */
  public function reset_resource ($type)
  {
    if (!isset($this->resources[$type])) return false;
    $resname = $this->resources[$type]['name'];
    unset($this->data[$resname]);
    $this->resources[$type]['added'] = [];
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
   * Add resource paths to the front of the list.
   */
  public function add_resource_paths ($type, $paths)
  {
    array_splice($this->resources[$type]['path'], 1, 0, $paths);
  }

}

