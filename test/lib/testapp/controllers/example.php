<?php

namespace TestApp\Controllers;

class Example extends \Lum\Controllers\Basic
{
  protected $layout = 'default';
  public function handle_default ($context)
  {
    $name = $this->name();
    $output = "Hello from $name";
    if (isset($context['name']))
      $output .= ", how are you {$context['name']}?";
    else
      $output .= '.';
    return $output;
  }

  public function handle_view ($context)
  {
    $this->data['title'] = 'A View';
    $this->data['name'] = isset($context['name']) ? $context['name'] : 'World';
    return $this->display();
  }
}

