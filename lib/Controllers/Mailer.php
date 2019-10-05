<?php

namespace Lum\Controllers;

/**
 * Trait to add a Mail template view with optional language-specific
 * features if we're also using the Messages trait.
 *
 * Define a property called 'email_path' if you want to override the default
 * mailer view folder of 'views/mail'.
 */
trait Mailer
{
  protected function __init_mailer_controller ($opts)
  {
    $core = \Lum\Core::getInstance();
    if (property_exists($this, 'email_path'))
    {
      if (is_array($this->email_path))
      {
        $dirs = $this->email_path;
      }
      else
      {
        $dirs = [$this->email_path];
      }
    }
    else
    {
      $dirs = ['views/mail'];
    }

    $lang = $this->get_prop('lang', Null);

    $core->mail_messages = 'views';
    foreach ($dirs as $dir)
    {
      if (isset($lang))
        $core->mail_messages->addDir($dir . '/' . $lang);

      $core->mail_messages->addDir($dir);
    }
  }

  /**
   * Get a Mailer object.
   *
   * @param (array|null) $fields (Optional) Fields for Mailer.
   * @param array $opts          (Optional) Options for Mailer.
   */
  public function mailer ($fields=null, $opts=[])
  {
    $opts['views'] = 'mail_messages';
    $mailer = new \Lum\Mailer($fields, $opts);
    return $mailer;
  }

}
