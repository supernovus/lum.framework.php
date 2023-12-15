<?php

namespace Lum\Controllers\Core;

use Lum\Controllers\{Core,Routes,URL,Uploads,ViewData,Wrappers};
use Lum\Controllers\Output\JSON_API;

/**
 * A simple abstract controller class for common features used in Web Apps.
 *
 * This combines the traits from both the Website and REST abstract classes,
 * and like those is designed as an example of how to use the Core class.
 *
 * @uses Routes
 * @uses URL
 * @uses Uploads
 * @uses ViewData
 * @uses Wrappers
 * @uses JSON_API
 */
abstract class App extends Core
{
  use Routes, URL, Uploads, ViewData, Wrappers, JSON_API;
}
