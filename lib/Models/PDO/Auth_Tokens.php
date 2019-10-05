<?php

namespace Lum\Models\PDO;

abstract class Auth_Tokens extends \Lum\DB\PDO\Model
{
  use \Lum\Models\Common\Auth_Tokens;

  protected $childclass  = '\Lum\Models\PDO\Auth_Token';
  protected $resultclass = '\Lum\DB\PDO\ResultSet';

  protected $token_cache = [];

  public function getToken ($id)
  {
    if (isset($this->token_cache[$id]))
    {
      return $this->token_cache[$id];
    }
    return $this->token_cache[$id] = $this->getRowById($id);
  }

  public function getUserToken ($uid)
  {
    if (is_object($uid))
      $uid = $uid->get_id();
    $cname = "user_$uid";
    if (isset($this->token_cache[$cname]))
    {
      return $this->token_cache[$cname];
    }
    $ucol = $this->user_field;
    return $this->token_cache[$cname] = $this->getRowWhere([$ucol=>$uid]);
  }

}