<?
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor
 *************************************************************************************************/

@session_start();
require_once( realpath(dirname(__FILE__).'/capabilities.php'));
require_once( realpath(dirname(__FILE__).'/utils.php'));
require_once( realpath(dirname(__FILE__).'/mysql.php'));
require_once( realpath(dirname(__FILE__).'/server.php'));
require_once( realpath(dirname(__FILE__).'/prefs.php'));
require_once( realpath(dirname(__FILE__).'/language.php'));

#-------------------------------------------------------------------------------------------------
# Here are the settings that we want available on a global basis.
#-------------------------------------------------------------------------------------------------

  define( 'ALBUMART_EXT', 'jpg,jpeg,gif,png' );
  define( 'MAX_PER_PAGE',   8 ); // Menus only

  define('MEDIA_TYPE_MUSIC',1);
  define('MEDIA_TYPE_PHOTO',2);
  define('MEDIA_TYPE_VIDEO',3);
  define('MEDIA_TYPE_RADIO',4);
  
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
        @define (strtoupper($k),$v);
  }

 

#-------------------------------------------------------------------------------------------------
# Check that the database is available before we continue with checks that should be performed 
# when every new session is initiated.
#-------------------------------------------------------------------------------------------------

if ( test_db() == 'OK' )
{

  // Check for Update
  if (internet_available() && (!isset($_SESSION["update"]["timeout"]) || $_SESSION["update"]["timeout"] < time() ))
  {
    if ( get_sys_pref('updates_enabled','YES') == 'YES')
    {
      // Check for program update
      $new_update_version = file_get_contents('http://update.swisscenter.co.uk/release/last_update.txt');
      $_SESSION["update"]["available"] = ( version_compare($new_update_version, swisscenter_version()) > 0);
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

  // Record the USER_AGENT_STRING reported by the browser (or hardware) so that we can get an idea
  // as to what the various boxes report, and therefore distinguish between them in the future.
  
  if (!isset($_SESSION["device"]))
  { 
    $device = array();
    $device["ip_address"]   = str_replace('\\','/',$_SERVER["REMOTE_ADDR"]);
    $device["agent_string"] = $_SERVER['HTTP_USER_AGENT'];
    $device["player_type"]  = get_player_type();
  
    if (strlen($device["ip_address"])>0)
    {
  	  db_sqlcommand("delete from clients where ip_address='".$device["ip_address"]."'");
  	  db_insert_row('clients',$device);
    }

    $_SESSION["device"] = $device;  
  }

}

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
