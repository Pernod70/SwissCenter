<?php
/**************************************************************************************************
   SWISScenter Source                                                              Nigel Barnes
 *************************************************************************************************/

require_once( realpath(dirname(__FILE__).'/prefs.php'));
require_once( realpath(dirname(__FILE__).'/../ext/registry/WindowsRegistry.php'));

/**
 * Checks whether ImageMagick is installed.
 *
 * @return boolean
 */
function imagemagick_check()
{
  $path = get_sys_pref('IMAGEMAGICK_PATH');
  if (is_windows())
  {
    $windowsRegistry = new WindowsRegistry();
    if ($windowsRegistry->KeyExists('HKEY_LOCAL_MACHINE\\SOFTWARE\\ImageMagick\\Current'))
    {
      $path = $windowsRegistry->ReadValue('HKEY_LOCAL_MACHINE\\SOFTWARE\\ImageMagick\\Current', 'BinPath', TRUE);
      $path = str_replace('\\','/',$path);
      set_sys_pref('IMAGEMAGICK_PATH',$path);
    }
  }
  return file_exists($path);
}

/**
 * Returns the version of the installed ImageMagick.
 *
 * @return string
 */
function imagemagick_version()
{
  if (is_windows())
  {
    $windowsRegistry = new WindowsRegistry();
    if ($windowsRegistry->KeyExists('HKEY_LOCAL_MACHINE\\SOFTWARE\\ImageMagick\\Current'))
      return $windowsRegistry->ReadValue('HKEY_LOCAL_MACHINE\\SOFTWARE\\ImageMagick\\Current', 'Version', TRUE);
    else
      return false;
  }
  else
    return false;
}

function imagemagick_available( $recheck = false)
{
  if ( !isset($_SESSION["IMAGEMAGICK_AVAILABLE"]) || $recheck )
    $_SESSION["IMAGEMAGICK_AVAILABLE"] = imagemagick_check();
  return $_SESSION["IMAGEMAGICK_AVAILABLE"];
}

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
