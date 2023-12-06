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
  use 
    \Lum\DB\Mongo\GetDoc, 
    \Lum\Models\Common\Users;

  protected $childclass  = '\Lum\Models\Mongo\User';
  protected $resultclass = '\Lum\DB\Mongo\Results';

  protected $user_cache  = [];      // A cache of known users.

  // Use the login field for `getDoc()/getUser()` calls.
  protected $get_doc_fields_property = 'login_field';

  protected $list_users_fields = null;

  /**
   * Get a user.
   *
   * This is now a wrapper around the `getDoc()` method.
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
    return $this->getDoc($identifier, $column);
  }

  /**
   * Get a list of users.
   */
  public function listUsers ($fields=null, $query=null)
  {
    if (!isset($fields)) $fields = $this->list_users_fields();
    if (!isset($query))  $query  = [];

    return $this->find($query, ['projection'=>$fields]);
  }

  protected function list_users_fields(): array
  {
    if (is_array($this->list_users_fields))
    {
      return $this->list_users_fields;
    }
    else
    {
      return [$this->login_field];
    }
  }

}

