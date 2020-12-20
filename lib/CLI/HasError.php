<?php

namespace Lum\CLI;

/**
 * A trait for application classes to use which provides a couple methods
 * for handling errors and displaying error messages.
 */
trait HasError
{
  use \Lum\Meta\HasProps;

  /**
   * This must be consumed by a class that has a getParamsInstance()
   * method which must return the initialized copy of the Params instance.
   */
  abstract protected function getParamsInstance ();

  /**
   * A small wrapper method to write a string to STDERR.
   *
   * @param string $message  The message to write to STDERR.
   * 
   * @return int  The number of bytes written, or false on error.
   */
  public static function stderr ($message)
  {
    return fwrite(STDERR, $message);
  }

  /**
   * A method to display an error message along with a quick
   * note about how to get further help information.
   *
   * @param string $errmsg  The error message to write to STDERR.
   * @param int $errcode  The error code to exit the app with (default: 1)
   *
   * Not sure why you'd want to do this, but if the $errcode is less than 0
   * or greater than 254, the exit() statement will not be called at all.
   *
   * Note this requires a class instance property to exist called
   * 'usage_message' which will be a template with the help placeholder,
   * which by default is {help} which will be replaced with the appropriate
   * usage command line parameter.
   */
  public function error ($errmsg, $errcode=1)
  {
    $params = $this->getParamsInstance();
    if (!($params instanceof Params))
    {
      throw new Exception("getParamsInstance did not return Params instance");
    }

    self::stderr($this->error_prefix);
    self::stderr("$errmsg\n");
    $usageGroup = $params->getUsageGroup();
    if (isset($usageGroup))
    {
      $usageTemplate = $this->get_prop('usage_message');
      if (!is_string($usageTemplate))
      {
        throw new Exception("The usage_message property was missing or invalid");
      }
      $usageParam = $usageGroup->getParam();
      $usageFlag = $usageParam->syntax();
      $usageTag = $params->help_placeholder;
      $usageText = str_replace($usageTag, $usageFlag, $usageTemplate);
      self::stderr("$usageText\n");
    }
    if ($errcode > -1 && $errcode < 255)
    {
      exit($errcode);
    }
  }

}