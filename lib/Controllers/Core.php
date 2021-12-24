<?php

namespace Lum\Controllers;

/** 
 * This class represents a controller foundation.
 *
 * The controller can have multiple models, and can load
 * templates consisting of a layout, and a screen.
 *
 * The contents of the screen will be made available as the
 * $view_content variable in the layout.
 *
 * You should create a base class to extend this that provides
 * any application-specific common controller methods.
 *
 * Your framework needs to define 'screens' and 'layouts' as view plugins.
 * This is as easy as:
 *
 *   $core->screens = ['plugin'=>'views', 'dir'=>'./views/screens'];
 *   $core->layouts = ['plugin'=>'views', 'dir'=>'./views/layouts'];
 *
 */
abstract class Core 
{
  use \Lum\Meta\ClassID,   // Adds $__classid and class_id()
      \Lum\Meta\HasDeps;   // needs(), wants(), get_prop(), set_prop(), etc.

  /**
   * Any models we have loaded.
   */
  protected array $models = [];

  /**
   * Set this to true if we want our object data to be an ArrayObject
   * instead of an array.
   */
  protected $object_data = false;

  /**
   * The data we are sending to the views.
   *
   * Will either be a PHP array, or an ArrayObject depending on the
   * value of the $object_data property.
   */
  protected $data = [];

  /**
   * Set this in your class to the screen name.
   *
   * If not specified we default to $this->name().
   */
  protected $screen;

  /**
   * Set this in your class to the layout.
   *
   * If not specified, we don't use a layout.
   */
  protected $layout;

  /**
   * Should we send an 'Expires' header in addition to the
   * Cache-Control header when sending API content?
   */
  protected $cache_expires = false;

  /**
   * Set this to the name of a method that will handle
   * exceptions and/or error, if you want the controller to
   * handle them. Otherwise, leave it undefined.
   */
  protected $exception_handler;

  // Will be set on each routing operation.
  protected $current_context;

  /**
   * Set this to the name of the template variable that will contain
   * the screen content when passing it to a layout template.
   *
   * Default: 'view_content'
   */
  protected $screen_layout_name = 'view_content';

  /**
   * Provide a default __construct() method that can chain a bunch of
   * constructors together. 
   *
   * The list of constructors that will be called, and in what order, is
   * dependent on the existence of a class property called $constructors.
   * If the property exists, and is an array, then it is a list of keys,
   * which expect a method called __construct_{$key}_controller() is defined
   * in your class (likely via trait composing.)
   *
   * If the property does not exist, then we will get a list of all methods
   * matching __construct_{word}_controller() and will call them
   * all in whatever order they were defined in.
   *
   * You can use the needs() protected method from your 
   * __construct_*_controller() methods to specify dependency order within
   * them. That is recommended rather than using the a $constructors property.
   *
   * @param array $opts  Constructor options.
   *
   * If using the Lum Core Controllers plugin, it will add certain properties
   * automatically when constructing the controller.
   *
   * That is the recommended way to load controllers, in which case you
   * instead of: $ctrl = new \MyApp\Controllers\MyController($opts);
   * You would call: $ctrl = $core->controllers('mycontroller', $opts);
   *
   * If the class defines a $exception_handler property, it is assumed to
   * be the name of a method that handles exceptions. A callback will be 
   * passed to the $core->setExceptionHandler() Lum Core method. 
   * Note that if more than one controller is loaded in a single PHP process, 
   * only the first controller loaded will register it's exception handler.
   * 
   */
  public function __construct ($opts=[])
  {
    if ($this->object_data)
      $this->data = new \ArrayObject();

    if (isset($this->exception_handler))
    {
      $except = [$this, $this->exception_handler];
      if (is_callable($except))
      {
        $core = \Lum\Core::getInstance();
        $core->setExceptionHandler($except);
      }
    }

    // Populate our $__classid property.
    if (isset($opts['__classid']))
    {
      $this->__classid = $opts['__classid'];
    }

    $dep_group_opts =
    [ // Set the options for our groups.
      'prefix'    => '__construct_',
      'postfix'   => '_controller',
      'deps_prop' => 'constructors',
    ];
    
    $this->_dep_group('constructors', $dep_group_opts, [$opts]);

  }

  /**
   * Initialize the Routing information.
   *
   * Called by the \Lum\Plugins\Router object ($core->router) when it
   * loads a controller before calling the handler method.
   *
   * Like the __construct() method this can call a bunch of 
   * __init_*_controller() methods.
   *
   * You can specify a $init_traits property which uses the same format
   * as the $constructors property.
   *
   * Or it will find all methods that match __init_{word}_controller() and
   * call each of them.
   *
   * Also like __construct(), your __init_*_controller() methods may use
   * the needs() methods to manually specify dependencies.
   *
   * @param object $context  The RouteContext object from the Router.
   */
  public function init_route ($context)
  {
    $this->current_context = $context;

    $dep_group_opts =
    [ // Set the options for our groups.
      'prefix'    => '__init_',
      'postfix'   => '_controller',
      'deps_prop' => 'init_traits',
    ];
    
    $this->_dep_group('init_route', $dep_group_opts, [$context]);

   }

  /**
   * Display the contents of a screen, typically within a common layout.
   * We use the $data class member as the array of variables to pass to
   * the template.
   *
   * A few controller hook methods may exist:
   *
   *  pre_render_page (&$screen, &$layout, &$opts)
   *    Called before the screen is rendered.
   *    Can modify the screen name, layout name, and/or options.
   *
   *  post_render_page (&$content, &$layout, &$opts)
   *    Called after the screen is rendered.
   *    Can modify the screen content, layout name, and/or options.
   *
   *  post_render_layout (&$content, $layout, $opts)
   *    Called after the layout is rendered.
   *    Can modify the layout content.
   *
   * None of the controller hooks are expected to return any values.
   * If you want to change any of the values sent to them, you must use
   * pass-by-reference parameters.
   *
   * @param array $opts  (Optional) Named options:
   *
   *   'screen'     If set, overrides the screen.
   *   'layout'     If set, overrides the layout.
   *
   * If 'screen' is not set, then we use $this->screen if it is set.
   * If neither 'screen' or $this->screen is set, we look for a screen
   * with the same basename as the controller.
   *
   * If 'layout' is not set, then we use $this->layout if it is set.
   * If neither 'layout' or $this->layout is set, we output the screen
   * directly without a layout.
   *
   * If we use a layout, then the contents of the screen will be available
   * in a variable determined by the $screen_layout_name property.
   */
  public function display ($opts=array())
  {
    // Get Lum.
    $core = \Lum\Core::getInstance();

    // Figure out which screen to display.
    if (isset($opts['screen']))
      $screen = $opts['screen'];
    elseif (isset($this->screen))
      $screen = $this->screen;
    else
      $screen = $this->name();

    // Now figure out what we want for a layout.
    if (isset($opts['layout']))
      $layout = $opts['layout'];
    else
      $layout = $this->layout;

    // Allow for some preparation code before rendering the page.
    if (is_callable([$this, 'pre_render_page']))
    {
      $this->pre_render_page($screen, $layout, $opts);
    }

    // Make sure the 'parent' is set correctly.
    if (!isset($this->data['parent']))
      $this->data['parent'] = $this;

    // Okay, let's get the screen output.
    // The screen may use the $parent object to modify our data.
    $page = $core->screens->load($screen, $this->data);

    // Now for post-page, pre-layout stuff.
    if (is_callable([$this, 'post_render_page']))
    {
      $this->post_render_page($page, $layout, $opts);
    }

    if ($layout)
    { // Please ensure your layout has a variable with the $screen_layout_name.
      $varname = $this->screen_layout_name;
      $this->data[$varname] = $page;
      $template = $core->layouts->load($layout, $this->data);
      if (is_callable([$this, 'post_render_layout']))
      {
        $this->post_render_layout($template, $layout, $opts);
      }
      return $template;
    }
    else
    { // We're going to directly return the content of the view.
      return $page;
    }
  }

  /**
   * Sometimes we don't want to display a primary screen with a layout,
   * but instead a sub-screen, with no layout, and using specified data.
   *
   * This method has no hooks or callbacks.
   *
   * @param string $screen    The name of the screen view to use.
   * @param array  $data      (Optional) Variables to send to the screen view.
   *
   * The $data defines the variables that will be made available to the
   * screen view template. If you do not specify a $data array, then the
   * $this->data class member will be used instead.
   */
  public function send_html ($screen, $data=Null)
  {
    if (is_null($data))
      $data = $this->data;
    $core = \Lum\Core::getInstance();
    $page = $core->screens->load($screen, $data);
    return $page;
  }

  /** 
   * Return the requested Model object.
   *
   * @param string $modelname  (Optional) The model to load.
   * @param array $modelopts   (Optional) Options to pass to model, see below.
   * @param mixed $loadopts    (Optional) Options specific to this, see below.
   * 
   * If the $model is not specified or is Null, then we assume the
   * model has the same name as the current controller (see `name()` below.)
   *
   * The $modelopts will be added to the parameters used in the class loader
   * (which will in turn be passed to the constructor of the Model class.)
   * If the `ModelConf` trait is loaded, the $modelopts will be passed to it
   * to have anything from the applicable model configuration added to it.
   *
   * If the specified $model has been loaded already by this controller,
   * by default we will return the cached copy, ignoring any new options.
   *
   * If $loadopts is an array, the options we support are:
   *
   *   'forceNew'               If set to True, we will always create a new
   *                            instance of the model, even if we've loaded
   *                            it before. If caching is on, it will override
   *                            the previously loaded instance.
   *
   *  'noCache'                 If set to true, we will not cache the model
   *                            instance loaded by this call.
   *
   * If $loadopts is a boolean, then it's a quick alias:
   *
   *   true       Same as ['forceNew'=>true, 'noCache'=>false]
   *   false      Same as ['forceNew'=>true, 'noCache'=>true]
   *
   * If $loadopts is null or any value other than one of the above, it's the
   * same as passing ['forceNew'=>false, 'noCache'=>false].
   *
   */
  public function model ($modelname=Null, $modelopts=[], $loadopts=[])
  {
    $core = \Lum\Core::getInstance();

    if (is_null($modelname))
    { // Assume the default model has the same name as the controller.
      $modelname = $this->name();
    }

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

    if 
    (
      !isset($this->models[$modelname]) ||
      (isset($loadopts['forceNew']) && $loadopts['forceNew'])
    )
    { 
      // If we have a populate_model_opts() method, call it.
      if (is_callable([$this, 'populate_model_opts']))
      {
        $modelopts = $this->populate_model_opts($modelname, $modelopts);
      }

      // Set our parent object.
      $modelopts['parent'] = $this;

      // Load the model instance.
      $instance = $core->models->load($modelname, $modelopts);

      // Cache the results.
      if (!isset($loadopts['noCache']) || !$loadopts['noCache'])
      {
        $this->models[$modelname] = $instance;
      }

      return $instance;
    }

    return $this->models[$modelname];
  }

  /**
   * Load another controller.
   */
  public function loadController (string $name, array $opts=[])
  {
    $core = \Lum\Core::getInstance();
    if (property_exists($this, 'user') && is_object($this->user))
    { // We're using the Auth trait, let's chain the user through.
      $opts['user'] = $this->user;
    }
    return $core->controllers->load($name, $opts);
  }

  /** 
   * Return our controller name.
   *
   * This is NOT the same as the classname.
   *
   * This is taken from the internal __classid which expects that this
   * was loaded using the $core->controllers plugin. If you are using the
   * $core->router plugin, it uses $core->controllers automatically.
   *
   * Generally this is the lowercase name of the controller class, with the 
   * matching Controllers namespace stripped off, and any remaining namespace
   * separator characters changed from '\' to '.' as that's what the loader
   * plugins use by default.
   *
   * Examples:
   *
   * \MyCompany\MyApp\Controllers\Home => 'home'
   * \MyCompany\MyApp\Controllers\Settings\Users => 'settings.users'
   *
   */
  public function name ()
  {
    return $this->__classid;
  }

}

// End of base class.

