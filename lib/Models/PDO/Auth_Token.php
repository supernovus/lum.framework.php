<?php

namespace Lum\Models\PDO;

abstract class Auth_Token extends \Lum\DB\PDO\Item
{
  use \Lum\Models\Common\Auth_Token;

  public function tokenId ()
  {
    return $this->id;
  }
}
