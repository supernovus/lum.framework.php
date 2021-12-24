<?php

namespace Lum\Controllers;

/**
 * Full controller, extension of Advanced controller.
 *
 * @deprecated See `Core` and `Core\*` base classes
 *
 * @uses JsonResponse
 * @uses XMLResponse
 */
abstract class Full extends Advanced
{
  use JsonResponse, XMLResponse;
}
