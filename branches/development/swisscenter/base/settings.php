<?
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor
 *************************************************************************************************/

session_start();
require_once("utils.php");
require_once("mysql.php");

  //
  // Here are the settings that we want available on a global basis, but we don't want the user
  // changing it (for example, it would mess up the screen display).
  //

  define( 'MAX_PER_PAGE',   8 );
  define( 'SCREEN_WIDTH',   620 );
  define( 'THUMB_W',        150 );
  define( 'THUMB_H',        225 );
  
  // Define media file extentsions
  
  define( 'MEDIA_EXT_MOVIE',  'avi,mpg,mpeg,vob' );
  define( 'MEDIA_EXT_MUSIC',  'mp3' );
  define( 'MEDIA_EXT_PHOTOS', 'jpeg,jpg,gif' );
  
/**************************************************************************************************
 * @return nOTHING
 * @param Text $heading
 * @param Text $text
 * @desc Displays a fatal error on the user's screen and exits processing immediately.
 *************************************************************************************************/

function fatal_error($heading,$text)
{
  echo "<center><p>&nbsp;<p><b>$heading</b><p>$text</center>";
  exit;
}
  
#-------------------------------------------------------------------------------------------------
# Check that the correct versions of PHP is present on the system.
#-------------------------------------------------------------------------------------------------

  // Check that the current version of PHP is equal to, or greater than 4.3.9
  // If it isn't then we should report the error to the user and exit immediately.
  if ( !version_compare(phpversion(),'4.3.9','>='))
    fatal_error('Your PHP version is too old.','You must have at least version 4.3.9 installed.');

  // Determein PHP location
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
  else 
    define ('SC_LOCATION', dirname($_SERVER['PHP_SELF']).'/');
  
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
  else 
  {
    if (is_showcenter()) 
      fatal_error('Your SwissCenter Configuration file is missing.','Please use the <font color="blue"">Configuration Utility</font> to create a configuration file');
    else
      fatal_error('Your SwissCenter Configuration file is missing.','Please use the <a href="/config/index.php">Configuration Utility<a> to create a configuration file');
  }
  
#-------------------------------------------------------------------------------------------------
# Determine the current user (currently, only one user is supported).
#-------------------------------------------------------------------------------------------------

  define ('CURRENT_USER',1);

#-------------------------------------------------------------------------------------------------
# Load the settings from the database if 
#  (a) the don't exist 
#  (b) we need to reload them or
#  (c) we've switched installations
#-------------------------------------------------------------------------------------------------

  if (! isset($_SESSION["opts"]) || $_SESSION["opts"]["reload"])
  {
    $opts = array();
    $user = array();
    $dirs = array();
    
    
    // Determine the current SwissCenter client & configuration
    $opts["current_client"]   = str_replace('\\','/',$_SERVER["REMOTE_ADDR"]);
    $opts["server_address"]   = $_SERVER['SERVER_ADDR'].":".$_SERVER['SERVER_PORT'];

    // Get the current screen type (PAL or NTSC)
    if (is_showcenter())
    {
      $text = @file_get_contents('http://'.$opts["current_client"].':2020/readsyb_options_page.cgi');  
      if (substr_between_strings($text, 'HasPAL','/HasPAL') == 1)
        $opts["screen"] = 'PAL';
      else
        $opts["screen"] = 'NTSC';
    }
    else 
    {
      $opts["screen"] = 'PAL';
    }
          
/*    
    // Showcenter clients...
    $box_id = db_value("select box_id from clients where ip_address='".$opts["current_client"]."'");
    if (empty($box_id))
    {
      $row = array('IP_ADDRESS'=>$opts["current_client"], 'BOX_ID'=>$_ENV["something"], 'USER_ID'=>$opts["user_id"]);
      db_sqlcommand("delete from clients where ip_address='".$opts["current_client"]."'");
      db_insert_row('clients',$row);
    }
*/    
    // Assign the settings array to the session.
    $_SESSION["opts"]         = $opts;  
  }
    

#-------------------------------------------------------------------------------------------------
# Check for internet connectivity
#-------------------------------------------------------------------------------------------------

  if ( !isset($_SESSION["internet"]) || $_SESSION["internet_check_timeout"] < time())
  {
    $temp = '';
    $socket = @fsockopen('www.google.com', 80, $temp, $temp, 1); 
    $_SESSION["internet_check_timeout"] = time()+300; // 5 mins
    $_SESSION["internet"] = ($socket ? true : false);
  }
  
#-------------------------------------------------------------------------------------------------
# Check for Update
#-------------------------------------------------------------------------------------------------

  if ($_SESSION["internet"] && (!isset($_SESSION["update"]["timeout"]) || $_SESSION["update"]["timeout"] < time() ))
  {
    $_SESSION["update"]["timeout"] = time()+86400; // 24 hours
    $last_update = file_get_contents('http://update.swisscenter.co.uk/release/last_update.txt');
    $_SESSION["update"]["available"] = ($last_update != $opts['last_update']);
  }

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
