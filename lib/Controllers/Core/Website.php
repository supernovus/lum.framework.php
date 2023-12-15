<?php

namespace Lum\Controllers\Core;

use Lum\Controllers\{Core,Routes,URL,Uploads,ViewData,Wrappers};

/**
 * A simple abstract controller class for displaying web pages.
 *
 * This is designed as an example of how to use the Core class.
 *
 * @uses Routes
 * @uses URL
 * @uses Uploads
 * @uses ViewData
 * @uses Wrappers
 */
abstract class Website extends Core
{
  use Routes, URL, Uploads, ViewData, Wrappers;
}
