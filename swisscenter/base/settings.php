<?
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor
 *************************************************************************************************/

@session_start();
require_once( realpath(dirname(__FILE__).'/capabilities.php'));
require_once( realpath(dirname(__FILE__).'/mysql.php'));
require_once( realpath(dirname(__FILE__).'/server.php'));
require_once( realpath(dirname(__FILE__).'/internet.php'));
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
  define('MEDIA_TYPE_WEB',5);
  
  define('THUMBNAIL_X_SIZE',130);
  define('THUMBNAIL_Y_SIZE',160);
  
#-------------------------------------------------------------------------------------------------
# Determine the location of the SwissCenter installation.
#-------------------------------------------------------------------------------------------------

  // Where is the SwissCenter installed?
  define('SC_LOCATION', str_replace('\\','/',realpath(dirname(dirname(__FILE__)))).'/' );
  
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
# Record the details of the client accessing the system, and download any new messages that may be
# available on the swisscenter.co.uk website.
#-------------------------------------------------------------------------------------------------

record_client_details();
download_new_messages();

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
