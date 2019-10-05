<?php

namespace Lum\Controllers;

/**
 * Advanced controller, extension of Basic controller.
 *
 * @uses ViewData
 * @uses Themes
 * @uses ModelConf
 * @uses Messages
 * @uses Auth
 * @uses Mailer
 * @uses Resources
 */
abstract class Advanced extends Basic implements \ArrayAccess
{
  use ViewData, Themes, ModelConf, Messages, Auth, Mailer, Resources;
}

