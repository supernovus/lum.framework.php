<?php

namespace Lum\Controllers\Core;

use Lum\Controllers\{Core,Routes,URL,Uploads,ViewData,Wrappers};
use Lum\Controllers\Output\JSON_API;

class App extends Core
{
  use Routes, URL, Uploads, ViewData, Wrappers, JSON_API;
}