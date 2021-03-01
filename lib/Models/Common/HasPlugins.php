<?php

namespace Lum\Models\Common;

/**
 * The name of the default loader for plugins.
 */
const PLUGINS_DEFAULT_LOADER = "models";

/**
 * Adds a plugin() method to Model classes.
 *
 * Plugins can exist in their own Lum\Core loader, or can simply
 * be in the Models loader in a nested namespace (which by default
 * is the loaded id of the current model, so if your model is 'users'
 * and you are trying to load a plugin called 'manager' then it would
 * call $core->models->load('users.manager')). 
 */
trait HasPlugins
{
  protected $loaded_plugins  = [];  // Plugins we've successfully loaded.
  protected $missing_plugins = [];  // Plugins that don't exist, we can skip.

  public function plugin ($plugname, $plugopts=[], $loadopts=[])
  {
    if (in_array($plugname, $this->missing_plugins))
    { // We've already noted this plugin doesn't exist. Bye!
      return null;
    }

    $core = \Lum\Core::getInstance();

    if ($loadopts === true)
    {
      $loadopts = ['forceNew'=>true, 'noCache'=>false];
    }
    elseif ($loadopts === false)
    {
      $loadopts = ['forceNew'=>true, 'noCache'=>'true'];
    }
    elseif (!is_array($loadopts))
    {
      $loadopts = [];
    }

    if (isset($loadopts['fatal']))
    { 
      $fatal = $loadopts['fatal'];
    }
    elseif (property_exists($this, 'plugin_load_fatal'))
    { 
      $fatal = $this->plugin_load_fatal;
    }
    else
    {
      $fatal = false;
    }

    if 
    (
      !isset($this->loaded_plugins[$plugname]) ||
      (isset($loadopts['forceNew']) && $loadopts['forceNew'])
    )
    { 
      // If we have a populate_plugin_opts() method, call it.
      if (method_exists($this, 'populate_plugin_opts'))
      {
        $plugopts = $this->populate_plugin_opts($plugname, $plugopts);
      }

      // Set our parent object.
      $plugopts['parent'] = $this;

      if (property_exists($this, 'plugin_loader'))
      { // We're using a custom loader.
        $loader = $this->plugin_loader;
        if (!isset($core->$loader) 
          || !($core->$loader instanceof \Lum\Loader\Instance))
        {
          if ($fatal)
          {
            throw new \Lum\Exception("$loader is not a valid Loader");
          }
          else
          {
            error_log("$loader is not a valid Loader");
            return null;
          }
        }
      }
      else
      { // We're loading using the models loader.
        $loader = PLUGINS_DEFAULT_LOADER;
      }
      
      if (property_exists($this, 'plugin_prefix'))
      {
        $prefix = $this->plugin_prefix;
      }
      elseif ($loader == PLUGINS_DEFAULT_LOADER 
        && method_exists($this, 'class_id'))
      {
        $prefix = $this->class_id();
      }
      else
      { // No prefix could be determined.
        if ($loader == PLUGINS_DEFAULT_LOADER)
        { // If the loader is models, the prefix MUST be set!
          if ($fatal)
          {
            throw new \Lum\Exception("Could not determine plugin namespace");
          }
          else
          {
            error_log("Could not determin plugin namespace");
            return null;
          }
        }
      }

      if (isset($prefix))
      { // We're using a prefix.
        $classname = "$prefix.$plugname";
      }
      else
      {
        $classname = $plugname;
      }

      try 
      {
        $instance = $core->$loader->load($classname, $plugopts);
      }
      catch (\Lum\Exception $e)
      {
        if ($fatal)
        { // Pass it through!
          throw $e;
        }
        else
        { // Mark it as missing, and return null.
          $this->missing_plugins[] = $plugname;
          return null;
        }
      }

      // Cache the results.
      if (!isset($loadopts['noCache']) || !$loadopts['noCache'])
      {
        $this->loaded_plugins[$plugname] = $instance;
      }

      return $instance;
    }

    return $this->loaded_plugins[$plugname];
  }
}