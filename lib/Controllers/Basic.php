<?php

namespace Lum\Controllers;

/**
 * Basic controller, Core controller + original set of traits.
 *
 * This may get deprecated and replaced at some point, but for now it
 * still works as a nice basic set of controller features.
 */
abstract class Basic extends Core
{
  use Routes, URL, Uploads, Wrappers, Output\JSON, Output\XML;
}
