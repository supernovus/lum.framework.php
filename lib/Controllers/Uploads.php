<?php

namespace Lum\Controllers;

trait Uploads
{
  /** 
   * Do we have an uploaded file?
   *
   * Uses the Lum\File::hasUpload() static method.
   *
   * @param String $fieldname  The upload field to look for.
   */
  public function has_upload ($fieldname, $context=null)
  {
    return \Lum\File::hasUpload($fieldname, $context);
  }

  /** 
   * Get an uploaded file. It will return Null if the upload does not exist.
   *
   * Uses the Lum\File::getUpload() static method.
   *
   * @param String $fieldname   The upload field to get.
   */
  public function get_upload ($fieldname, $context=null)
  {
    return \Lum\File::getUpload($fieldname, $context);
  }

}
