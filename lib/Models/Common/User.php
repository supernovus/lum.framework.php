<?php

namespace Lum\Models\Common;

/**
 * Common trait for User models.
 *
 * Not generally used directly, but composed into the abstract base classes
 * in the PDO and Mongo namespaces.
 */
trait User
{
  abstract public function save ($opts=[]);
  abstract public function get_id ();

  /**
   * Change our password.
   *
   * @param String $newpass      The new password
   * @param Bool   $autosave     Save automatically (default True)
   */
  public function changePassword ($newpass, $autosave=True)
  { // We auto-generate a unique token every time we change the password.
    $hash = $this->parent->hash_type();
    $auth = $this->parent->get_auth();
    $tfield = $this->parent->token_field();
    $hfield = $this->parent->hash_field();
    $this->$tfield = hash($hash, time());
    $this->$hfield = $auth->generate_hash($this->$tfield, $newpass);
    if ($autosave) $this->save();
  }

  /** 
   * Reset our reset code to a unique value.
   *
   * @param Bool $autosave    Save automatically (default True)
   *
   * @return String           The new reset code is returned.
   */
  public function resetReset ($autosave=True)
  {
    $rfield = $this->parent->reset_field();
    $reset = $this->$rfield = uniqid(base64_encode($this->id), True);
    if ($autosave) $this->save();
    return $reset;
  }

  /**
   * Change our login field, ensuring that it is unique.
   *
   * @param String $newlogin     The new login value.
   * @param Bool   $autosave     Save automatically (default True)
   *
   * @return Bool                False means e-mail address already in use.
   *                             True means we updated successfully.
   */
  public function changeLogin ($newlogin, $autosave=True)
  {
    $lfield = $this->parent->login_field();
    if ($this->parent->getUser($newlogin, $lfield))
    {
      return False; // Sorry, e-mail already in use.
    }
    $this->$lfield = $newlogin;
    if ($autosave) $this->save();
    return True;
  }

  /**
   * Get the user's display name.
   *
   * This default implementation will look for a few possible fields.
   * For the full name:  'name', 'fullName', 'full_name', 'email'.
   * For the short name: 'shortName', 'nickname', 'name', 'email'.
   *
   * If your app has different requirements, feel free to override it.
   *
   * @param bool $short  If true, return the user's short name.
   *                     This might be their first name in many western 
   *                     cultures, or a nick name, or something else.
   *                     If false, we return the user's full name.
   *                     Default: false
   *
   * @return string  The user's name, depending on the $short option.
   */
  public function getName ($short=false)
  {
    if ($short)
    {
      $names = ['shortName', 'nickname', 'name', 'email'];
    }
    else
    {
      $names = ['name', 'fullName', 'full_name', 'email'];
    }

    foreach ($names as $name)
    {
      if (isset($this->$name))
      { // First one found gets used.
        return $this->$name;
      }
    }
  }

  // A wrapper for Users::send_activation_email()
  public function send_activation_email ($opts=[])
  {
    return $this->parent->send_activation_email($this, $opts);
  }

  // A wrapper for Users::send_forgot_email()
  public function send_forgot_email ($opts=[])
  {
    return $this->parent->send_forgot_email($this, $opts);
  }

}
