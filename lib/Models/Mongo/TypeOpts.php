<?php

namespace Lum\Models\Mongo;

trait TypeOpts
{
  protected function checkTypeOpts ($to)
  {
    $isArray = ($to instanceof \MongoDB\Model\BSONArray);
    $isDoc   = ($to instanceof \MongoDB\Model\BSONDocument);
    return [$isArray, $isDoc];    
  }

  protected function areOptsArray ($to)
  {
    return ($to instanceof \MongoDB\Model\BSONArray);
  }

  protected function areOptsDoc ($to)
  {
    return ($to instanceof \MongoDB\Model\BSONDocument);
  }
}
