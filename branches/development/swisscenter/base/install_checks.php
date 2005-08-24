<?
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor
 *************************************************************************************************/

require_once("settings.php");

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
  check_mod('zip');
  check_mod('mysql');
  check_mod('xml');
  check_mod('session');
  
  $_SESSION["install_check"]["extensions"] = 'YES';
}

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

if (is_windows() && !isset($_SESSION["install_check"]["task_sched"]))
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
