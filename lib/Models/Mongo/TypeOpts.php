<?php

namespace Lum\Models\Mongo;

trait TypeOpts
{
  public function checkTypeOpts ($to)
  {
    $isArray = ($to instanceof \MongoDB\Model\BSONArray);
    $isDoc   = ($to instanceof \MongoDB\Model\BSONDocument);
    return [$isArray, $isDoc];    
  }

  public function areOptsArray ($to)
  {
    return ($to instanceof \MongoDB\Model\BSONArray);
  }

  public function areOptsDoc ($to)
  {
    return ($to instanceof \MongoDB\Model\BSONDocument);
  }
}
