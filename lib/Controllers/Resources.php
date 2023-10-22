<?php

namespace Lum\Controllers;

/**
 * Compatibility wrapper for original Resources trait.
 * 
 * Uses the new ResourceManager, plus the Legacy set of resources.
 * 
 * @deprecated New projects should use ResourceManager directly.
 */
trait Resources
{
  use ResourceManager, ResourceManager\Legacy;
}
