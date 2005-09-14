<?
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor
 *************************************************************************************************/

include_once('server.php');

#-------------------------------------------------------------------------------------------------
# Determine screen type (currently only PAL or NTSC - no support for HDTV).
#-------------------------------------------------------------------------------------------------

function get_screen_type()
{  
  if ( !isset($_SESSION["display_type"]) )
  {
    if (is_showcenter())
    {
      $text = @file_get_contents('http://'.client_ip().':2020/readsyb_options_page.cgi');  
      if (substr_between_strings($text, 'HasPAL','/HasPAL') == 1)
        $_SESSION["display_type"] = 'PAL';
      else
        $_SESSION["display_type"] = 'NTSC';
    }
    else 
      $_SESSION["display_type"] = 'PAL';
  }
  
  return $_SESSION["display_type"];
}

#-------------------------------------------------------------------------------------------------
# Define system capabilities
#-------------------------------------------------------------------------------------------------

  // Media extensions supported by this hardware device.
  define( 'MEDIA_EXT_MOVIE',   'avi,mpg,mpeg,vob,wmv,asf' );
  define( 'MEDIA_EXT_MUSIC',   'mp3,wma' );
  define( 'MEDIA_EXT_PHOTOS',  'jpeg,jpg,gif' );
  
  // Maximum number of entries within a playlist.
  define( 'MAX_PLAYLIST_SIZE', 2000);
  define( 'THUMBNAIL_X_SIZE',  80);
  define( 'THUMBNAIL_Y_SIZE',  80);  
  
  if (get_screen_type() == 'NTSC')
  {
    define( 'SCREEN_WIDTH',   620 );
    define( 'SCREEN_HEIGHT',  418 );
  }
  else 
  {
    define( 'SCREEN_WIDTH',   620 );
    define( 'SCREEN_HEIGHT',  500 );
  }

?>
