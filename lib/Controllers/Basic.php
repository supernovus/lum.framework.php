<?php

namespace Lum\Controllers;

/**
 * Basic controller, Core controller + original set of traits.
 *
 * @deprecated See `Core` and `Core\*` base classes
 *
 * @uses Routes
 * @uses URL
 * @uses Uploads
 * @uses Wrappers
 * @uses Output\JSON
 * @uses Output\XML
 */
abstract class Basic extends Core
{
  use Routes, URL, Uploads, Wrappers, Output\JSON, Output\XML;
}
