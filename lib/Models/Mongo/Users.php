<?php

namespace Lum\Models\Mongo;
use \MongoDB\BSON\ObjectId;

/**
 * Users (MongoDB) base class, as used by our Controllers\Auth trait.
 *
 * This is a bare minimum, and should be extended in your application models.
 *
 * By default we expect the 'email' field to be used as the plain text login.
 * You can change that to 'username' or something else if you so desire, or
 * override the getUser() method to support multiple fields. Just remember that
 * it MUST match the primary key as one of the fields.
 */

abstract class Users extends \Lum\DB\Mongo\Model
{
  use \Lum\Models\Common\Users;

  protected $childclass  = '\Lum\Models\Mongo\User';
  protected $resultclass = '\Lum\DB\Mongo\Results';

  protected $user_cache  = [];      // A cache of known users.

  /**
   * Get a user.
   *
   * @param Mixed $identifier    Either the numeric primary key,
   *                             or a stringy login field (default 'email')
   *
   * @param Str $column          (optional) An explicit database column.
   *
   * @return Mixed               Either a User row object, or Null.
   */
  public function getUser($identifier, $column=null)
  {
    if (is_array($identifier))
    {
      $ident = json_encode($identifier);
    }
    else
    {
      $ident = (string)$identifier;
    }

    #error_log("getUser($ident, ".json_encode($column).")");

    if (isset($column))
    {
      $ident = $column.'_'.$ident;
    }

    if (isset($this->user_cache[$ident]))
    { // It's already been cached.
      return $this->user_cache[$ident];
    }

    // Look up the user in the database.
    if (isset($column))
    {
      return $this->getUserWith($ident, $column, $identifier);
    }
    elseif ($identifier instanceof ObjectId // BSON ObjectId object.
      || is_array($identifier)              // Extended JSON representation.
      || ctype_xdigit($identifier))         // An ObjectId string.
    { // Look up by userId.
      return $this->user_cache[$ident] 
           = $this->getDocById($identifier);
    }
    elseif (is_array($this->login_field))
    { // Try a list of login fields.
      foreach ($this->login_field as $field)
      {
        $user = $this->getUserWith($ident, $field, $identifier);
        if (isset($user))
        {
          return $user;
        }
      }
    }
    elseif (is_string($this->login_field))
    { // Look up by a single default field.
      $field = $this->login_field;
      return $this->getUserWith($ident, $field, $identifier);
    }
  }

  protected function getUserWith($ident, $field, $value)
  {
    return $this->user_cache[$ident]
         = $this->findOne([$field=>$value]);
  }

  /**
   * Get a list of users.
   */
  public function listUsers ($fields=[])
  {
    if (count($fields) == 0)
    {
      $fields[$this->login_field] = true;
    }
    return $this->find([], ['projection'=>$fields]);
  }

}

