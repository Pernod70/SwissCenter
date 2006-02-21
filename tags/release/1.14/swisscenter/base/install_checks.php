<?
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor
 *************************************************************************************************/

require_once("settings.php");

#-------------------------------------------------------------------------------------------------
# Displays a fatal error on the user's screen and exits processing immediately.
#-------------------------------------------------------------------------------------------------

function fatal_error($heading,$text)
{
  echo "<center><p>&nbsp;<p><b>$heading</b><p>$text</center>";
  exit;
}
  
#-------------------------------------------------------------------------------------------------
# Check that the correct versions of PHP is present on the system.
#-------------------------------------------------------------------------------------------------

if ( !version_compare(phpversion(),'4.3.9','>='))
  fatal_error(str('PHP_VERSION_TITLE'),str('PHP_VERSION_TEXT'));

#-------------------------------------------------------------------------------------------------
# Check the necessary extensions have been compiled into PHP
#-------------------------------------------------------------------------------------------------

function check_mod( $module )
{
  if (!extension_loaded($module))
    if (is_windows())
      fatal_error(str('MODULE_MISSING_TITLE'),str('MODULE_MISSING_WINDOWS','[b]'.$module.'[/b]'));
    else 
      fatal_error(str('MODULE_MISSING_TITLE'),str('MODULE_MISSING_LINUX','[b]'.$module.'[/b]'));
}

if (!isset($_SESSION["install_check"]["extensions"]))
{ 
  check_mod('gd');
  check_mod('mbstring');
  check_mod('mysql');
  check_mod('xml');
  check_mod('session');
//  check_mod('zip');  // Only needed for downloading styles from the website
  
  $_SESSION["install_check"]["extensions"] = 'YES';
}

#-------------------------------------------------------------------------------------------------
#- Checks to see if the SwissCenter installation directory is readable/writeable.
#-------------------------------------------------------------------------------------------------

if (! is_writable(SC_LOCATION) || ! is_readable(SC_LOCATION))
  fatal_error(str('MISSING_PERMS_TITLE'),str('MISSING_PERMS_TEXT'));
  
$info = stat(SC_LOCATION.'/index.php');
if (is_unix() && ($info[4]==0 || $info[5]==0))
  fatal_error(str('ROOT_INSTALL_TITLE'),str('ROOT_INSTALL_TEXT'));
  
#-------------------------------------------------------------------------------------------------
# Check that the swisscenter ini file exists and display an error message if it doesn't.
#-------------------------------------------------------------------------------------------------

if (! file_exists(SC_LOCATION.'/config/swisscenter.ini'))
  fatal_error(str('MISSING_INI_TITLE'),str('MISSING_INI_TEXT'));
  
#-------------------------------------------------------------------------------------------------
# Tests to see if there is a database to connect to.
#-------------------------------------------------------------------------------------------------

if ( test_db(DB_HOST,DB_USERNAME,DB_PASSWORD,DB_DATABASE) != 'OK')
  fatal_error(str('MISSING_DB_TITLE'),str('MISSING_DB_TEXT'));

#-------------------------------------------------------------------------------------------------
# If running on windows, then check that the "Task Scheduler" service is running.
#-------------------------------------------------------------------------------------------------

if (is_windows() && !is_server_simese() && !isset($_SESSION["install_check"]["task_sched"]))
{
  $services = syscall('net start');
  if ( strpos($services,'Task Scheduler') === false)
    fatal_error(str('MISSING_SCHED_TITLE'),str('MISSING_SCHED_TEXT'));

  $_SESSION["install_check"]["task_sched"] = 'YES';
}

#-------------------------------------------------------------------------------------------------
# Whenever a new session starts, check that there is some media in the database. If not, inform
# the user that they need to add a location or do a media search.
#-------------------------------------------------------------------------------------------------

if (!isset($_SESSION["install_check"]["media_locations"]))
{ 
  $media_count = db_value("select count(*) from media_locations");

  if ($media_count ==0)
    fatal_error(str('MISSING_MEDIA_TITLE'),str('MISSING_MEDIA_TEXT'));

  $_SESSION["install_check"]["media_locations"] = 'YES';  
}

?>
