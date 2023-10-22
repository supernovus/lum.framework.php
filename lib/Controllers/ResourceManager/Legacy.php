<?php

namespace Lum\Controllers\ResourceManager;

/**
 * Enable the legacy set of pre-defined resource groups.
 * 
 * @deprecated Use config files to load resource groups.
 */
trait Legacy
{
  /**
   * The original property name for the Resource Manager.
   * Will be merged with new-style resources if defined.
   */
  protected $resources =
  [
    'js' =>
    [
      'path' => ['scripts', 'scripts/nano', 'scripts/ext'],
      'groups' =>
      [ 
        '#common' =>
        [ // The base scripts we expect everywhere.
          'jquery', 
          'core',
          'helpers',
          'observable',
          'hash',
          'json.jq', 
          'disabled.jq',
          'exists.jq',
        ],
        '#webcore' =>
        [ // A simplified web app model core. No rendering engine specified.
          '#common',
          'oquery',
          'modelapi',
          'viewcontroller',
        ],
        '#webcore_ws' =>
        [ // A version of web core for working with web services.
          '#webcore',
          'webservice',
          'promise',
          'modelapi/ws_model',
        ],
        '#base64' =>
        [ // Base64 encoding and decoding.
          'crypto/components/core-min',
          'crypto/components/enc-base64-min',
        ],
        '#ace' =>
        [ // The Ace editor, it uses an embedded module loader for components.
          'ace/src-min-noconflict/ace'
        ],
        '#editor' => 
        [ // The Lum.Editor component.
          '#common',
          '#ace', 
          '#base64',
          'editor',
        ],
      ],
      'urls' => 
      [
        '@google_charts' => 'https://www.gstatic.com/charts/loader.js',
      ],
      'added' => [], 
    ],
    'css' =>
    [
      'path' => ['style'],
    ],
  ];
}
