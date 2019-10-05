<?php

namespace Lum\Auth;

/**
 * An object to handle authentication via simple user-hashes.
 *
 * An extremely basic user authentication system using a simple
 * hash-based approach. It has no specifications as to the user model,
 * other than it needs to store the hash provided by the generate_hash
 * method.
 *
 * The default storage model is to store the object in the PHP session.
 * We use the Lum Core Session plugin to achieve this.
 * You can disable the autostoring, and store it yourself, or you can
 * create a subclass and override store(), getInstance() and update() methods.
 *
 */
class Simple
{
  /**
   * @var bool  Enable logging?
   */
  public $log = False;

  /**
   * @var bool  Store in session on construction?
   */
  public $autoStore = true;

  // If this is set to null, we will default to using hash() instead.
  protected $passwordHash = PASSWORD_DEFAULT;
  protected $pwHashOpts   = [];   // Options for password_hash().
  protected $pwHashHeader = '$';  // Hashes from password_hash() start with.

  protected $hashType = 'sha256'; // Default hash algorithm if using hash().

  protected $userid;              // The currently logged in user.

  protected $timeout = 0;         // Num if idle seconds until timeout.
  protected $accessed;            // Last accessed time.

  // A failsafe mechanism for where you can't control the
  // location of the session_start() call in relations to
  // the SimpleAuth object being created.
  public static function preLoad () { return True; }

  /**
   * Build a new Simple auth instance.
   *
   * @param array $opts  (Optional) Named options:
   *
   *  'log'          (bool)     Override the $log property.
   *
   *  'store'        (bool)     Override the $autoStore property.
   *
   *  'hash'         (string)   Type of hashing to use (default: 'sha256').
   *
   *  'passwordHash' (mixed)    Type of password hasing to use.
   *                            Default: PASSWORD_DEFAULT (PHP constant.)
   *                            If you set to null, password_hash() isn't used.
   *
   *  'passwordHashOpts' (array)  Options for password_hash().
   *
   */
  public function __construct ($opts=array())
  {
    if (isset($opts['log']))
    { 
      $this->log = $opts['log'];
    }

    if (isset($opts['store']))
    {
      $this->autoStore = $opts['store'];
    }

    if (isset($opts['hash']))
    {
      $this->hashType = $opts['hash'];
    }

    if (isset($opts['passwordHash']))
    {
      $this->passwordHash = $opts['passwordHash'];
    }

    if (isset($opts['passwordHashOpts']))
    {
      $this->pwHashOpts = $opts['passwordHashOpts'];
    }

    if ($this->autoStore)
    {
      $this->store();
    }
  }

  /**
   * Store the Auth\Simple instance in the PHP session.
   *
   * You can override this in sub-classes if you want to store it in
   * a database or somewhere else.
   */
  public function store ()
  {
    $core = \Lum\Core::getInstance();
    $core->sess->SimpleAuth = $this;
  }

  /**
   * Get an instance of the Auth\Simple class.
   *
   * If an instance is found in the PHP session, we return that.
   * Otherwise we create a new instance and return it.
   *
   * @param array $opts  (Optional) Options to pass to the constructor.
   *                     Only used if we create a new instance.
   */
  public static function getInstance ($opts=array())
  {
#    error_log('SimpleAuth::getInstance('.json_encode($opts).')');
    $core = \Lum\Core::getInstance();
    $core->sess; // Make sure the plugin is initialized.
    if (isset($core->sess->SimpleAuth) && is_object($core->sess->SimpleAuth))
    { 
#      error_log(" -- loading session");
      return $core->sess->SimpleAuth;
    }
#    error_log(" -- creating session");
    return new static($opts);
  }

  // A method to update details. In this implementation we don't
  // use it, but if you change how session details are stored you'll
  // want to make your own version.
  protected function update () {}

  /**
   * Generate a password hash.
   *
   * If the 'passwordHash' is set to null, we generate a hash using the
   * hash() function. 
   *
   * If 'passwordHash' is set to a valid password_hash() algorith, 
   * we generate a password using password_hash().
   *
   * @param string $token    The unique token for the user.
   * @param string $pass     The password for the user.
   * @param bool $forceHash  (Optional) If true, we force the use of hash().
   *                         You probably don't ever want to use this option.
   *                         It's used internally to migrate from the older
   *                         hash() based password hashes to the newer
   *                         password_hash() hashes.
   *                         
   */
  public function generate_hash ($token, $pass, $forceHash=false)
  {
    if ($forceHash || !isset($this->passwordHash))
    {
      return hash($this->hashType, trim($token.$pass));
    }
    else
    {
      return password_hash(trim($token.$pass), $this->passwordHash);
    }
  }

  /**
   * See if a user is logged in or not.
   *
   * Also handles the session timeouts. We get the current time, and
   * compare it against the last accessed time. If the number of seconds
   * are greater than the timeout period, the session is expired.
   * If it isn't, the session is still valid, and we update the last
   * accessed time to the current time.
   *
   * @return mixed  Will return the user id if a user is logged in.
   *                User ids may be strings or integers.
   *                Will return boolean false if no user is logged in.
   *
   */
  public function is_user ()
  { 
    if (isset($this->userid))
    {
      if ($this->timeout > 0)
      {
        $curtime = time();
        $oldtime = $this->accessed;
        if (($curtime - $oldtime) > $this->timeout)
        {
          return false;
        }
        $this->accessed = $curtime;
      }
      return $this->userid;
    }
    return False;
  }

  /**
   * Process a user login.
   *
   * @param (string|int) $userid  The user id we are authenticating.
   * @param string $pass  The password that was specified.
   * @param string $userhash  The user's current password hash.
   * 
   * @param mixed $usertoken (Optional) The token used along with the
   *                         password to generate the hash. If it's null or
   *                         not specified we use the $userid as the token.
   *
   * @param int $timeout  (Optional) If specified, it's the number of seconds
   *                      that this session should remain valid without any
   *                      activity before it times out.
   *
   * @return bool  Was the login attempt successful or not?
   * 
   */
  public function login 
    ($userid, $pass, $userhash, $usertoken=Null, $timeout=Null)
  {
    // If we don't specify a user token, assume the same as userid.
    if (is_null($usertoken))
      $usertoken = $userid;

    if (isset($timeout) && $timeout > 0)
    {
      $this->timeout = $timeout;
      $this->accessed = time();
    }

    if ($this->check_credentials($usertoken, $pass, $userhash))
    { 
      $this->setUser($userid);
      if ($this->log) error_log("User '$userid' logged in.");
      return true;
    }
    return false;
  }

  /**
   * Set the user id of the currently authenticated user.
   *
   * This is generally never called directly. Use login() instead.
   *
   * @param (string|int) $userid  The user id to set.
   */
  public function setUser ($userid)
  {
    $this->userid = $userid;
    $this->update();
  }

  /**
   * Logout of this session.
   *
   * Has a couple of optional parameters that will affect the PHP session.
   *
   * @param bool $destroy_session  If true, we kill the PHP session.
   * @param bool $restart_session  If true, we restart the PHP session.
   *                               Only used if $destroy_session was true too.
   *
   */
  public function logout ($destroy_session=False, $restart_session=False)
  {
    if ($this->log)
    {
      $userid = $this->userid;
      error_log("User '$userid' logged out.");
    }
    $this->userid    = Null;
    $this->update();

    if ($destroy_session)
    { // Destroy the entire session. A good way to log out.
      $core = \Lum\Core::getInstance();
      $core->sess->kill($restart_session);
    }
  }

  /**
   * Check credentials.
   *
   * Normally not called directly, this is what login() uses to check
   * to see if the password specified is correct.
   *
   * @param string $usertoken  The user token to use in the hash.
   * @param string $pass       The password to use in the hash.
   * @param string $userhash   The password hash that should match.
   *
   * If the $userhash starts with a '$' character, we assume the hash
   * was generated using password_hash() and use password_verify() to
   * validate the credentials.
   *
   * If $userhash does not start with a '$' character, we assume it was
   * generated using the hash() algorithm, and we call generate_hash()
   * with the $forceHash option set to true and then compare it's output to
   * the $userhash.
   *
   * @return bool  Did the hash generated from the token and password match
   *               the specified $userhash?
   *
   */
  public function check_credentials ($usertoken, $pass, $userhash)
  {
    if ($this->is_password_hash($userhash))
    { // The hash is in the new format.
      return password_verify(trim($usertoken.$pass), $userhash);
    }
    else
    { // Use the old hash algorithm.
      $checkhash = $this->generate_hash($usertoken, $pass, true);
      return (strcmp($userhash, $checkhash) == 0);
    }
  }

  /**
   * See if a hash string was generated by password_hash()
   *
   * This is dead simple, if the hash string starts with '$' we assume it
   * was generated by password_hash(). If it doesn't we assume it wasn't.
   *
   * @param string $userhash  The hash we are checking.
   * @return bool  Was it generated by password_hash() ?
   */
  public function is_password_hash ($userhash)
  {
    $pwheader = $this->pwHashHeader;
    return (substr($userhash, 0, strlen($pwheader)) == $pwheader);
  }

  /**
   * See if the specified hash is in the currently used hashing format.
   *
   * This is also really simple, we call is_password_hash() on the specified
   * hash, and then see if the 'passwordHash' option is null or not.
   *
   * @param string $userhash  The hash we are checking.
   * @return bool  Is the hash in the current format?
   */
  public function hash_is_current ($userhash)
  {
    $ispw = $this->is_password_hash($userhash);
    if (isset($this->passwordHash))
    { // We want a password_hash() generated hash.
      return $ispw;
    }
    else
    { // We want a hash() generated hash.
      return (!$ispw);
    }
  }

} // class Lum\Auth\Simple

