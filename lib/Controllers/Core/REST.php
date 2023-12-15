<?php

namespace Lum\Controllers\Core;

use Lum\Controllers\{Core,Routes,URL,Uploads};
use Lum\Controllers\Output\JSON_API;

/**
 * A simple abstract controller class for JSON-based REST APIs.
 *
 * This is designed as an example of how to use the Core class.
 *
 * @uses Routes
 * @uses URL
 * @uses Uploads
 * @uses JSON_API
 */
abstract class REST extends Core
{
  use Routes, URL, Uploads, JSON_API;
}
