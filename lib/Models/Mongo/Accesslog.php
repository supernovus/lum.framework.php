<?php

namespace Lum\Models\Mongo;

/**
 * A basic access log.
 *
 * Records a bunch of data to a database for auditing purposes.
 */
abstract class AccessLog extends \Lum\DB\Mongo\Model
{
  use \Lum\Models\Common\AccessLog;

  protected $childclass  = '\Lum\Models\Mongo\AccessRecord';
  protected $resultclass = '\Lum\DB\Mongo\Results';

  public $known_fields =
  [
    'success' => false, 'message' => null, 'context' => null, 
    'headers' => null, 'userdata' => null, 'timestamp' => 0,
  ];
}

class AccessRecord 
extends \Lum\DB\Mongo\Item 
implements \Lum\Models\Common\AccessRecord 
{}

