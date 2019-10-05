<?php

namespace Lum\Controllers;

/**
 * Enhanced controller, extension of Basic controller.
 *
 * @uses JsonResponse
 * @uses XMLResponse
 */
abstract class Enhanced extends Basic
{
  use JsonResponse, XMLResponse;
}
