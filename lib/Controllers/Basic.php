<?php

namespace Lum\Controllers;
use Lum\Exception;

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
abstract class Basic 
{
  use \Lum\Meta\ClassID;  // Adds $__classid and class_id()

  /**
   * Any models we have loaded.
   */
  public $models  = [];

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
   * Should we enable the IE JSON output hack?
   */
  protected $json_ie_support = true;

  /**
   * Should we enable the IE cache output hack?
   */
  protected $cache_ie_support = true;

  /**
   * Should we use text/xml instead of application/xml?
   */
  protected $use_xml_text = false;

  /**
   * When using the send_json() method, this defines a method name in an object
   * that can be used to convert the object to JSON.
   */
  protected $to_json_method = 'to_json';

  /**
   * When using the send_json() method, this defines a method name in an object
   * that can be used to convert the object to a PHP array.
   *
   * This is only used if the $to_json_method method was not found.
   */
  protected $to_array_method = 'to_array';

  /**
   * When using the send_xml() method, this defines a method name in an object
   * that can be used to convert the object to an XML string.
   */
  protected $to_xml_method  = 'to_xml';

  // A list of constructors we've already called.
  protected $called_constructors = [];

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

  // For internal use only.
  private $_constructed = false;

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

    if (property_exists($this, 'constructors') && is_array($this->constructors))
    { // Use a defined list of constructors.
      $constructors = $this->constructors;
      $fullname = False;
    }
    else
    { // Build a list of all known constructors, and call them.
      $constructors = preg_grep('/__construct_\w+_controller/i', 
        get_class_methods($this));
      $fullname = True;
    }

    $debug = $this->get_prop('debug', False);

    if ($debug)
      error_log("Constructor list: ".json_encode($constructors));

    $this->needs($constructors, $opts, $fullname);

    $this->_constructed = true;
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

    if (property_exists($this, 'init_traits') && is_array($this->init_traits))
    {
      $constructors = $this->init_traits;
      $fullname = False;
    }
    else
    {
      $constructors = preg_grep('/__init_\w+_controller/i',
        get_class_methods($this));
      $fullname = True;
    }

    $debug = $this->get_prop('debug', False);

    if ($debug)
      error_log("Init traits list: ".json_encode($constructors));

    $this->needs($constructors, $context, $fullname);
  }

  /**
   * Specify requirements for your custom constructor.
   *
   * May be used in __construct_*_controller() methods,
   * or __init_*_controller() methods. It will automatically determine which
   * type of method to call based on if construction is complete or not.
   *
   * @param (array|string) $constructor  Either a constructor name, or an
   *                                     array of constructor names to load.
   *
   * @param mixed $opts  Options to pass to the method.
   *                     __construct_*_controller() methods need an array.
   *                     __init_*_controller() methods need a RouteContext.
   *
   * @param bool $fullname (Optional, default false) Is the name passed a full
   *                       method name? This is generally for internal use only.
   *                       You probably don't want to use it.
   *
   * @param bool $nofail   (Optional, default false) Don't throw an exception
   *                       if we can't find a method to call. Again, you
   *                       probably don't want to use this option directly.
   *                       See the wants() method instead.
   *
   * @return void
   */
  protected function needs ($constructor, $opts=[], $fullname=False, $nofail=false)
  {
    if (is_array($constructor))
    {
      foreach ($constructor as $const)
      {
        $this->needs($const, $opts, $fullname, $nofail);
      }
      return;
    }

    if ($fullname)
    {
      $method = $constructor;
    }
    else
    {
      if ($this->_constructed)
      {
        $method = "__init_{$constructor}_controller";
      }
      else
      {
        $method = "__construct_{$constructor}_controller";
      }
    }

    if 
    ( isset($this->called_constructors[$method]) 
      && $this->called_constructors[$method]
    ) return; // Skip already called constructors.

    if (method_exists($this, $method))
    {
      $this->called_constructors[$method] = true;
      $this->$method($opts);
    }
    elseif (!$nofail)
    {
      throw new Exception("Invalid constructor '$constructor' requested.");
    }
  }

  /**
   * A version of needs() that doesn't throw an exception if any of the
   * requested functions don't exist.
   *
   * @param (array|string) $constructor  See needs().
   * @param mixed $opts  See needs().
   * @param bool $fullname  See needs().
   * @return void
   */
  protected function wants ($constructor, $opts=[], $fullname=False)
  {
    return $this->needs($constructor, $opts, $fullname, true);
  }

  /**
   * If a property exists, return it's value, otherwise return a default.
   *
   * @param string $property  The property we are looking for.
   * @param mixed $default  (Optional, default null) The default value.
   * @return mixed
   */
  public function get_prop ($property, $default=Null)
  {
    if (property_exists($this, $property))
      return $this->$property;
    else
      return $default;
  }

  /**
   * Set a property if it exists.
   *
   * @param string $property  The property we want to set if it exists.
   * @param mixed $value  The value we want to set the property to.
   * @return bool  Did the property exist?
   */
  public function set_prop ($property, $value)
  {
    if (property_exists($this, $property))
    {
      $this->$property = $value;
      return true;
    }
    return false;
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
   * Sometimes we want to send JSON data instead of a template.
   *
   * @param mixed $data         The data to send (see below)
   * @param mixed $opts         Options, see below.
   *
   * If the $data is a PHP Array, or a scalar value, then it will be processed 
   * using the json_encode() function. 
   * In this case, there is one recognized option:
   *
   *   'fancy'     If set to True, we will use JSON_PRETTY_PRINT option.
   *
   * If the $data is an Object, then how we encode it is determined by
   * what methods it has available:
   *
   * If it has a method by the name of the $to_json_method property,
   * it will be called, and passed the $opts as it's sole parameter.
   *
   * If it has a method by the name of the $to_array_method property,
   * it will be called and passed the $opts as it's sole parameter, and
   * then the array generated will be passed to json_encode().
   *
   * If it has neither of those methods, it will be passed directly to
   * json_encode() (with only the 'fancy' option being recognized.)
   *
   * If the $data is a string, it will be assumed to be a valid JSON string,
   * and will be sent as is.
   *
   * Anything else will fail and throw an Exception.
   */
  public function send_json ($data, $opts=[])
  { 
    $core = \Lum\Core::getInstance();
    $core->output->json($this->json_ie_support);
    $core->output->nocache($this->cache_ie_support);

    $json_opts = 0;
    if 
    (
      isset($opts['fancy']) 
      && $opts['fancy'] 
      && defined('JSON_PRETTY_PRINT') // PHP 5.4+
    )
    {
      $json_opts = JSON_PRETTY_PRINT;
    }

    if (is_string($data))
    { // A passthrough for JSON strings.
      $json = $data;
    }
    elseif (is_array($data) || is_scalar($data)) 
    { // Encode the array/scalar as JSON and send it.
      $json = json_encode($data, $json_opts);
    }
    elseif (is_object($data))
    { // Magic for converting objects to JSON.
      $meth1 = $this->to_json_method;
      $meth2 = $this->to_array_method;
      if (is_callable([$data, $meth1]))
      {
        $json = $data->$meth1($opts);
      }
      elseif (is_callable([$data, $meth2]))
      {
        $array = $data->$meth2($opts);
        $json = json_encode($array, $json_opts);
      }
      else
      {
        $json = json_encode($data, $json_opts);
      }
    }
    else
    {
      throw new Exception('Unsupported data type sent to send_json()');
    }
    return $json;
  }

  /** 
   * Sometimes we want to send XML data instead of a template.
   *
   * @param mixed $data        The data to send (see below)
   * @param mixed $opts        Options, used in some cases (see below)
   *
   * If $data is a string, we assume it's valid XML and pass it through.
   *
   * If $data is a SimpleXML Element, DOMDocument or DOMElement, we use the
   * native calls of those libraries to convert it to an XML string.
   * One caveat: we assume that DOMElement objects have an ownerDocument,
   * and if they don't, this method will fail.
   *
   * If the $data is another kind of object, and has a method as per the
   * $this->to_xml_method (default: 'to_xml') then it will be called with
   * the $opts as its first parameter.
   *
   * Anything else will throw an Exception.
   */
  public function send_xml ($data, $opts=[])
  {
    $core = \Lum\Core::getInstance();
    $core->output->xml($this->use_xml_text);
    $core->output->nocache($this->cache_ie_support);

    $fancy = isset($opts['fancy']) 
           ? (bool)$opts['fancy'] 
           : isset($opts['reformat'])
           ? (bool)$opts['reformat']
           : false;

    if (is_string($data))
    { // Passthrough.
      $xml = $data;
    }
    elseif (is_object($data))
    {
      $method = $this->to_xml_method;

      if ($data instanceof \SimpleXMLElement)
        $xml = $data->asXML();
      elseif ($data instanceof \DOMDocument)
        $xml = $data->saveXML();
      elseif ($data instanceof \DOMElement)
        $xml = $data->ownerDocument->saveXML();
      elseif (is_callable([$data, $method]))
      {
        $xml = $data->$method($opts);
        $fancy = false; // the method must do the formatting.
      }
      else
        throw new Exception('Unsupported object sent to send_xml()');
    }
    else
    {
      throw new Exception('Unsupported data type sent to send_xml()');
    }

    if ($fancy)
    { // Reformat the output.
      $dom = new \DOMDocument('1.0');
      $dom->preserveWhiteSpace = false;
      $dom->formatOutput = true;
      $dom->loadXML($xml);
      $xml = $dom->saveXML();
    }

    return $xml;
  }

  /** 
   * Return the requested Model object.
   *
   * @param string $modelname  (Optional) The model to load.
   * @param array $modelopts   (Optional) Options to pass to model, see below.
   * @param mixed $loadopts    (Optional) Options specific to this, see below.
   * 
   * If the $model is not specified or is Null, then we assume the
   * model has the same basename as the current controller.
   *
   * The $modelopts will be added to the parameters used in the class loader
   * (which will in turn be passed to the constructor of the Model class.)
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
   * Return our controller base name.
   *
   * This is taken from the internal __classid which expects that this
   * was loaded using the $core->controllers plugin. If you are using the
   * $core->router plugin, it uses $core->controllers automatically.
   */
  public function name ()
  {
    return $this->__classid;
  }

  /** 
   * Do we have an uploaded file?
   *
   * Uses the Lum\File::hasUpload() static method.
   *
   * @param String $fieldname  The upload field to look for.
   */
  public function has_upload ($fieldname, $context=null)
  {
    return \Lum\File::hasUpload($fieldname, $context);
  }

  /** 
   * Get an uploaded file. It will return Null if the upload does not exist.
   *
   * Uses the Lum\File::getUpload() static method.
   *
   * @param String $fieldname   The upload field to get.
   */
  public function get_upload ($fieldname, $context=null)
  {
    return \Lum\File::getUpload($fieldname, $context);
  }

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

// End of base class.

