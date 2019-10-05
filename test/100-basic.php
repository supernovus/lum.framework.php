<?php

require_once 'vendor/autoload.php';

\Lum\Autoload::register('./test/lib');

$t = new \Lum\Test();

$t->plan(4);

$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['REQUEST_URI']    = '/';
$_SERVER['CONTENT_TYPE']   = 'text/plain';

$core = \Lum\Core::getInstance();
$core->controllers->addNS("\\TestApp\\Controllers");
$core->controllers->use_screens(false);
$core->layouts->addDir("test/views/layouts");
$core->screens->addDir("test/views/screens");
$core->router->setDefault('example');
$core->router->add(
[
  'uri' => '/view', 
  'controller' => 'example', 
  'action' => 'handle_view'
]);

$output = $core->router->route();

$expected = 'Hello from example.';
$t->is($output, $expected, 'output from a controller');

$_REQUEST['name'] = 'Bob';

$output = $core->router->route();

$expected = rtrim($expected, '.').', how are you Bob?';

$t->is($output, $expected, 'output with a parameter');

unset($_REQUEST['name']);

$_SERVER['CONTENT_TYPE']   = 'text/html';
$_SERVER['REQUEST_URI'] = '/view';

$expected = "<html>\n<head>\n<title>A View</title>\n</head>\n<body>\n<h1>A View</h1>\n<div id=\"content\">\n<p>Hello World</p>\n</div>\n</body>\n</html>";

$output = $core->router->route();

$t->is($output, $expected, 'output from a view');

$_REQUEST['name'] = 'Bob';

$expected = str_replace('World','Bob',$expected);

$output = $core->router->route();

$t->is($output, $expected, 'output from a view with a parameter');

# TODO: more tests.

echo $t->tap();
return $t;

