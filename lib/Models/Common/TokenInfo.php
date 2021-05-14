<?php

namespace Lum\Models\Common;

/**
 * A pseudo-static class for Auth Token constants.
 */
class TokenInfo
{
  /**
   * The format code for F1 (device-independent) tokens.
   */
  const FORMAT_1 = "01";

  /**
   * The format code for F2 (device-specific) tokens.
   */
  const FORMAT_2 = "02";

  /**
   * A list of valid, currently supported format codes.
   */
  const VALID_FORMATS =
  [
    self::FORMAT_1,
    self::FORMAT_2,
  ];

  /**
   * Default hash algorithm.
   */
  const DEFAULT_HASH_TYPE = 'sha256';

  /**
   * Parses a token (either an app token or auth token) and returns
   * the individual portions of the token as an array.
   *
   * @param string $token  The token string to be parsed.
   * @param bool $assoc  Return an associative array?
   *
   * @return array|string  If there is an error, this will be a string.
   *
   * If there is no error, this will be an array.
   *
   * If $assoc is true, the keys will be:
   *
   *  "format" => The format code of the token.
   *  "tid"    => The token id.
   *  "hash"   => The hash component of the token.
   *
   * If $assoc is false, a flat array will be returned:
   *
   *  [$tid, $hash, $format]
   *
   * The order is due to backwards compatibility with the original parseToken
   * method from the Auth_Tokens trait.
   *
   */
  static function parseToken (string $token, bool $assoc)
  {
    $format = substr($token, 0, 2);
    if (!in_array($format, self::VALID_FORMATS, true))
    {
      return 'invalid_format';
    }

    $len = intval(substr($token, 2, 2));
    if (!$len)
    {
      return 'invalid_length';
    }

    $tid = substr($token, 4, $len);

    $offset = $len + 4;
    $hash = substr($token, $offset);

    if ($assoc)
    {
      return ["format"=>$format, "tid"=>$tid, "hash"=>$hash];
    }
    else
    {
      return [$tid, $hash, $format];
    }
  }

  static function authHash (string $tid, string $ahash, 
    ?string $sid=null, 
    string $htype=self::DEFAULT_HASH_TYPE)
  {
    $authdata = trim($tid) . trim($ahash);
    if (isset($sid))
    {
      $authdata .= trim($sid);
    }
    return hash($htype, $authdata);
  }

}