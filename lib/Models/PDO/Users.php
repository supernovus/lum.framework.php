<?php

namespace Lum\Models\PDO;

/**
 * Users (DB table) base class, as used by our Controllers\Auth trait.
 *
 * This is a bare minimum, and should be extended in your application models.
 *
 * By default we expect the 'email' field to be used as the plain text login.
 * You can change that to 'username' or something else if you so desire, or
 * override the getUser() method to support multiple fields. Just remember that
 * it MUST match the primary key as one of the fields.
 */

abstract class Users extends \Lum\DB\PDO\Model
{
  use \Lum\Models\Common\Users;

  protected $childclass  = '\Lum\Models\PDO\User';
  protected $resultclass = '\Lum\DB\PDO\ResultSet';

  protected $user_cache  = [];      // A cache of known users.

  protected $list_users_fields = null;

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
    // First, check to see if it's cached.
    if (isset($this->user_cache[$identifier]))
    {
      return $this->user_cache[$identifier];
    }

    // Look up the user in the database.
    if (isset($column))
    {
      return $this->user_cache[$identifier]
           = $this->getRowWhere([$column=>$identifier]);
    }
    elseif (is_numeric($identifier))
    { // Look up by userId.
      return $this->user_cache[$identifier] 
           = $this->getRowById($identifier);
    }
    else
    { // Look up by e-mail address.
      $field = $this->login_field;
      return $this->user_cache[$identifier] 
           = $this->getRowWhere([$field=>$identifier]);
    }
  }

  /**
   * Get a list of users.
   */
  public function listUsers ($fields=null, $where=null)
  {
    if (!isset($fields)) $fields = $this->list_users_fields();

    $select = ['cols'=>$fields];
    if (isset($where))
    { // Using a WHERE statement.
      $select['where'] = $where;
    }

    return $this->select($select);
  }

  protected function list_users_fields(): array
  {
    if (is_array($this->list_users_fields))
    {
      return $this->list_users_fields;
    }
    else
    {
      return [$this->primary_key, $this->login_field];
    }
  }

}

