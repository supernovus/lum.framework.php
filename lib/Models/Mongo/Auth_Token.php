<?php

namespace Lum\Models\Mongo;

abstract class Auth_Token extends \Lum\DB\Mongo\Item
{
  use \Lum\Models\Common\Auth_Token;

  public function tokenId ()
  {
    return (string)$this->_id;
  }
}
