<?php

require_once 'cli_common.php';

class MyScript extends TestApp
{
  protected function initialize_params ($params, $opts)
  { // First call the TestApp method.
    parent::initialize_params($params, $opts);

    // Now add our commands.
    $params
      ->command('add', ['listed'=>true, 'listDesc'=>"Add user"])
      ->toggle(true, 'A', "Add a user")
      ->value(true, 'u', 'username', 'Username for new user', 'username')
      ->optional(false, 'staff', 'role', 'Staff role for new user', 'User is staff with default role', null, ['present'=>'employee'])
      ->flag(false, 's', 'Seniority', 'lvl')
      ->command('delete', ['listed'=>true, "label"=>"Delete user"])
      ->toggle(true, 'D', "Delete a user")
      ->value(true, 'u', 'username', 'Username to delete', 'username')
      ->group('default', ['visible'=>true, "label"=>"Default options"])
      ->flag(false, 'v', "Verbosity level", 'verbose')
      ->flag(false, 'verbose', "Verbosity level");
  }

  protected function handle_add ($opts, $params)
  {
    return $this->debug_opts($opts, $params, "handle_add()\n");
  }

  protected function handle_delete ($opts, $params)
  {
    return $this->debug_opts($opts, $params, "handle_delete()\n");
  }

}

$app = new MyScript();
echo $app->run();
