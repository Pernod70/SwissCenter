<?
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor
 *************************************************************************************************/

session_start();
require_once("utils.php");
require_once("mysql.php");
require_once("server.php");
require_once("prefs.php");
require_once("language.php");

#-------------------------------------------------------------------------------------------------
# Here are the settings that we want available on a global basis, but we don't want the user
# changing it (for example, it would mess up the screen display).
#-------------------------------------------------------------------------------------------------

  define( 'MAX_PER_PAGE',   8 ); // Menus only
  define( 'SCREEN_WIDTH',   620 );
  define( 'THUMB_W',        150 );
  define( 'THUMB_H',        225 );
  
#-------------------------------------------------------------------------------------------------
# Check that the correct versions of PHP is present on the system.
#-------------------------------------------------------------------------------------------------

  // Determine PHP location
  if ( is_windows() )
    define ('PHP_LOCATION', str_replace('\\','/',$_SERVER["SCRIPT_FILENAME"]));
  else
    define ('PHP_LOCATION', trim(syscall('which php')));

#-------------------------------------------------------------------------------------------------
# Determine the location of the SwissCenter installation, and required files.
#-------------------------------------------------------------------------------------------------

  // Where is the SwissCenter installed?
  if (!empty($_SERVER['DOCUMENT_ROOT']))
    define ('SC_LOCATION',str_replace('\\','/',os_path($_SERVER["DOCUMENT_ROOT"],true)));
  elseif (!empty($_SERVER['PHP_SELF']))
    define ('SC_LOCATION', str_replace('\\','/',dirname($_SERVER['PHP_SELF']).'/'));
  else 
    define ('SC_LOCATION', str_replace('\\','/',dirname($_SERVER["argv"][0]).'/'));
  
  // Ensure a logfile location is defined
  if ( !defined('LOGFILE'))
  {
    define('LOGFILE',os_path(SC_LOCATION.'log/support.log'));
    update_ini(os_path(SC_LOCATION.'config/swisscenter.ini'),'LOGFILE',LOGFILE);
  }

#-------------------------------------------------------------------------------------------------
# Process the SwissCenter configuration file which contains the MySQL database connection details
# and location of the support log file.
#-------------------------------------------------------------------------------------------------

  // Defines the database parameters
  if (file_exists(SC_LOCATION.'/config/swisscenter.ini'))
  {
    // Read file
    foreach( parse_ini_file(SC_LOCATION.'config/swisscenter.ini') as $k => $v)
      if (!empty($v))
        define (strtoupper($k),$v);
  }


#-------------------------------------------------------------------------------------------------
# Check for Update
#-------------------------------------------------------------------------------------------------

  if (internet_available() && (!isset($_SESSION["update"]["timeout"]) || $_SESSION["update"]["timeout"] < time() ))
  {
    if ( get_sys_pref('updates_enabled','YES') == 'YES')
    {
      // Check for program update
      $new_update_version = file_get_contents('http://update.swisscenter.co.uk/release/last_update.txt');
      $_SESSION["update"]["available"] = ($new_update_version > get_sys_pref('last_update',get_sys_pref('database_version')) );
    }
    
    if ( get_sys_pref('messages_enabled','YES') == 'YES')
    {
      // Check for new messages
      $last_update = db_value("select max(added) from messages");
      $messages = file_get_contents("http://update.swisscenter.co.uk/messages.php?last_check=".urlencode($last_update));
    
      if (!empty($messages))
      {
        foreach (unserialize($messages) as $mesg)
        {
          unset ($mesg["MESSAGE_ID"]);
          db_insert_row('messages',$mesg);
        }
      }
    }
    
    // Check again in 24 hours
    $_SESSION["update"]["timeout"]   = time()+86400; 
  }

#-------------------------------------------------------------------------------------------------
# Record the USER_AGENT_STRING reported by the browser (or hardware) so that we can get an idea
# as to what the various boxes report, and therefore distinguish between them in the future.
#-------------------------------------------------------------------------------------------------
  
  if (!isset($_SESSION["device"]))
  { 
    $device = array();
    $device["ip_address"]   = str_replace('\\','/',$_SERVER["REMOTE_ADDR"]);
    $device["agent_string"] = $_SERVER['HTTP_USER_AGENT'];
  
	  db_sqlcommand("delete from clients where ip_address='$ip'");
	  db_insert_row('clients',$device);

    $_SESSION["device"] = $device;  
  }

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
