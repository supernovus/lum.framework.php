<?php

namespace Lum\CLI\ParamGroup;

use \Lum\CLI\{Exception,Params,Param,ParamGroup};
use \Lum\Text\Util as TU;

/**
 * A layout using two columns.
 *
 * Columns:
 *
 * | Parameter name and value if any   | Description of parameter |
 *
 * Example:
 *
 *  -a                    Toggle short parameter toggle_desc here.
 *  -b <value>            Valued short parameter value_desc here.
 *  -c                    Optional short parameter toggle_desc here.
 *  -c=<value>            Optional short parameter value_desc here.
 *  --long1               Toggle long parameter toggle_desc here.
 *  --long2 <value>       Valued long parameter value_desc here.
 *  --long3               Optional long parameter toggle_desc here.
 *  --long3=<long_value>  Optional long parameter value_desc here.
 *
 */
class TwoColumn extends Format
{
  use \Lum\CLI\ParamOpts;

  const OPT_EXCEPTIONS = ['params','group','col_widths'];
  const OPT_ALIASES =
  [
    "minCol" => "min_col_width",
    "maxCol" => "max_col_width",
  ];

  const RESERVED_WIDTH = 4; // <optname>!![value_placeholder]!!<description>

  protected $min_col_width = 4;
  protected $max_col_width = 40;
  protected $col_widths = [];

  protected function initialize_layout ($opts)
  {
    $this->__construct_opts($opts, self::OPT_EXCEPTIONS, self::OPT_ALIASES);

    $full = $this->params->max_row_width;
    $min  = $this->min_col_width;
    $in   = $this->params->indent_params;

    $col1 = $min + self::RESERVED_WIDTH;
    $rest = $full - $in - $col1;
    $this->col_widths = [$col1, $rest];
  }

  public function update_layout (Param $param)
  {
    $full = $this->params->max_row_width;
    $in   = $this->params->indent_params;
    $res  = self::RESERVED_WIDTH;

    $max = $this->max_col_width;
    $col1 = $res;
    $col1 += strlen($param->syntax());
    if ($param->has_value)
    {
      $col1 += strlen($param->value_placeholder);
    }
    if ($col1 > $max) throw new Exception("Combined parameter/value names too long");
    if ($col1 > $this->col_widths[0])
    { // The width needs to increase!
      $this->col_widths[0] = $col1;
      $this->col_widths[1] = $full - $in - $col1;
    }
  }

  public function render ()
  {
    $output = '';

    $params = $this->group->getParams();
    $indent = $this->params->indent_params;

    $w1 = $this->col_widths[0];
    $w2 = $this->col_widths[1];

    $in = str_repeat(' ', $indent);
    
    foreach ($params as $param)
    {
      $output .= $in;
      $col1 = $param->syntax();
      $col1v = null;
      $col2v = null;
      $col2t = null;
      if ($param->has_value)
      {
        $col2v = trim($param->value_desc);
        if ($param->value_required)
        { // Mandatory value.
          $col1 .= ' <' . $param->value_placeholder . '> ';
        }
        else
        { // Optional value.
          $col1v = $col1 . '=<' . $param->value_placeholder . '> ';
          $col2t = trim($param->toggle_desc);
        }
      }
      else
      {
        $col2t = trim($param->toggle_desc);
      }

      $output .= TU::pad($col1, $w1); // Always include this.

      if (isset($col1v))
      { // Optional value.
        $output .= TU::pad($col2t, $w2)
          ."\n$in"
          . TU::pad($col1v, $w1)
          . TU::pad($col2v, $w2);
      }
      elseif (isset($col2v))
      { // Mandatory value.
        $output .= TU::pad($col2v, $w2);
      }
      else
      { // Toggle.
        $output .= TU::pad($col2t, $w2);
      }
      $output .= "\n"; // End the line.
    }

    return $output;
  }

}