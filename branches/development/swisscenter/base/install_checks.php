<?
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor

   This file contains a number of functions to check the installation of the SwissCenter software
   on a server.

   Checks still to implement
   -------------------------
   
   * SwissCenter configuration
     * The swisscenter files are owned by the webserver user.
     * The swisscenter files (and dirs) are R/W by the webserver user.
     
   * Scheduler
     * Linux   : cron is enabled for the webserver user
     
  *************************************************************************************************/

require_once( realpath(dirname(__FILE__).'/settings.php'));
require_once( realpath(dirname(__FILE__).'/file.php'));

#-------------------------------------------------------------------------------------------------
# PHP checks
#-------------------------------------------------------------------------------------------------

function check_php_version()
{
  return version_compare(phpversion(),'4.3.9','>=');
}


function check_php_ini_location()
{
  return (php_ini_location() !== false);
}


function check_php_required_modules()
{
  foreach ( get_required_modules_list() as $module)
    if (!extension_loaded($module))
      return false;
      
  return true;
}


function check_php_suggested_modules()
{
  foreach ( get_suggested_modules_list() as $module)
    if (!extension_loaded($module))
      return false;
      
  return true;
}


#-------------------------------------------------------------------------------------------------
# MySQL checks
#-------------------------------------------------------------------------------------------------

function check_mysql_connect()
{
  # Do we have defined constants for database connectivity?
  if ( !defined('DB_HOST') || !defined('DB_DATABASE') || !defined('DB_USERNAME') || !defined('DB_PASSWORD'))
    return false;

  if ( ($db = @mysql_pconnect( DB_HOST, DB_USERNAME, DB_PASSWORD )) && mysql_select_db(DB_DATABASE, $db) )
    return true;
  else 
    return false;
}


function check_mysql_version()
{
  $version = db_value("select version()");
  return version_compare($version,'4.0','>=');  
}


#-------------------------------------------------------------------------------------------------
# Webserver checks
#-------------------------------------------------------------------------------------------------

// If the server is not Simese or Apache, then we just assume that it will work. Better to do that
// than simply report an error!

function check_web_version()
{
  if (is_server_simese())
    return version_compare(simese_version(),'1.31','>=');
  elseif (is_server_apache())
    return version_compare(apache_version(),'2.0','>=');
  else 
    return true; 
}


#-------------------------------------------------------------------------------------------------
# SwissCenter configuration
#-------------------------------------------------------------------------------------------------

function check_swiss_write_log_dir()
{
  return is_writeable(SC_LOCATION.'/log/');  
}


function check_swiss_ini_file()
{
  return file_exists(SC_LOCATION.'/config/swisscenter.ini');
}


function check_swiss_media_locations()
{
  return (db_value("select count(*) from media_locations") > 0);
}


#-------------------------------------------------------------------------------------------------
# Scheduler
#-------------------------------------------------------------------------------------------------

function check_server_scheduler()
{
  if (is_windows())
  {
    // Windows - Is Simese > xxx or is the task scheduler service running?
    if (is_server_simese() )
    {
      if (version_compare(simese_version(),'1.31','>='))
        return true;
      else 
        return false;
    }
    else
    {
      return is_task_scheduler_running();
    }
  }
  else 
  {
    // Linux - So check that crontab is available for use.
    return true;
  }
}


#-------------------------------------------------------------------------------------------------
# Performs all the individual checks and puts them into an array. Typically, this function will be
# called twice - once from the webserver, and once via the CLI. The results will then be compared
# and presented to the user in the configuration screen.
#-------------------------------------------------------------------------------------------------

Function get_check_results()
{
  $results = array();
  $results['PHP ini file']           = check_php_ini_location();
  $results['PHP required mods']      = check_php_required_modules();
  $results['PHP suggested mods']     = check_php_suggested_modules();
  $results['PHP version']            = check_php_version();
  $results['MYSQL connect']          = check_mysql_connect();
  $results['MYSQL version']          = check_mysql_version();
  $results['SWISS ini file']         = check_swiss_ini_file();
  $results['SWISS media locs']       = check_swiss_media_locations();
  $results['SWISS write log']        = check_swiss_write_log_dir();
  $results['SERVER scheduler']       = check_server_scheduler();
  return $results;
}

#-------------------------------------------------------------------------------------------------
#-------------------------------------------------------------------------------------------------
#-------------------------------------------------------------------------------------------------
#-------------------------------------------------------------------------------------------------
#-------------------------------------------------------------------------------------------------

#-------------------------------------------------------------------------------------------------
#- Checks to see if the SwissCenter installation directory is readable/writeable.
#-------------------------------------------------------------------------------------------------

if (! is_writable(SC_LOCATION) || ! is_readable(SC_LOCATION))
  fatal_error(str('MISSING_PERMS_TITLE'),str('MISSING_PERMS_TEXT'));
  
$info = stat(SC_LOCATION.'/index.php');
if (is_unix() && ($info[4]==0 || $info[5]==0))
  fatal_error(str('ROOT_INSTALL_TITLE'),str('ROOT_INSTALL_TEXT'));
  
#-------------------------------------------------------------------------------------------------
# Tests to see if there is a database to connect to.
#-------------------------------------------------------------------------------------------------

if ( test_db(DB_HOST,DB_USERNAME,DB_PASSWORD,DB_DATABASE) != 'OK')
  fatal_error(str('MISSING_DB_TITLE'),str('MISSING_DB_TEXT'));

#-------------------------------------------------------------------------------------------------
# If running on windows, then check that the "Task Scheduler" service is running.
#-------------------------------------------------------------------------------------------------

#-------------------------------------------------------------------------------------------------
#                                             End of file
#-------------------------------------------------------------------------------------------------
?>
