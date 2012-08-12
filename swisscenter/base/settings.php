<?php
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
  define('MEDIA_TYPE_TV',6);
  define('MEDIA_TYPE_INTERNET_TV',7);

  define('IRADIO_SHOUTCAST',1);
  define('IRADIO_LIVERADIO',2);
  define('IRADIO_LIVE365',3);
  define('IRADIO_ICECAST',4);
  define('IRADIO_STEAMCAST',5);
  define('IRADIO_TUNEIN',6);

  define('THUMBNAIL_X_SIZE',140);
  define('THUMBNAIL_Y_SIZE',210);
  define('THUMBNAIL_LARGE_X_SIZE',210);
  define('THUMBNAIL_LARGE_Y_SIZE',460);

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
# Suppress DateTime warnings.
#-------------------------------------------------------------------------------------------------

  date_default_timezone_set(@date_default_timezone_get());

#-------------------------------------------------------------------------------------------------
# Record the details of the client accessing the system.
#-------------------------------------------------------------------------------------------------

  record_client_details();

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
