<?php

namespace Lum\Controllers\Core;

use Lum\Controllers\{Core,Routes,URL,Uploads};
use Lum\Controllers\Output\JSON_API;

class REST extends Core
{
  use Routes, URL, Uploads, JSON_API;
}