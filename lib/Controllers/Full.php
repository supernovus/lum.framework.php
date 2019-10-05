<?php

namespace Lum\Controllers;

/**
 * Full controller, extension of Advanced controller.
 *
 * @uses JsonResponse
 * @uses XMLResponse
 */
abstract class Full extends Advanced
{
  use JsonResponse, XMLResponse;
}
