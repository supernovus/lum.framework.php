<?php

namespace Lum\Controllers;

/**
 * Enhanced controller, extension of Basic controller.
 *
 * @deprecated See `Core` and `Core\*` base classes
 *
 * @uses JsonResponse
 * @uses XMLResponse
 */
abstract class Enhanced extends Basic
{
  use JsonResponse, XMLResponse;
}
