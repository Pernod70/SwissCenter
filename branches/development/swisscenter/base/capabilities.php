<?
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor
 *************************************************************************************************/

include_once('server.php');
include_once('utils.php');

#-------------------------------------------------------------------------------------------------
# Returns the type of hardware player that the SwissCenter is communicating with.
#-------------------------------------------------------------------------------------------------

function get_player_type()
{
  if     ( strpos($_SERVER['HTTP_USER_AGENT'],'-NST-')>0 )
    return 'NEUSTON';
  elseif ( strpos($_SERVER['HTTP_USER_AGENT'],'-PIN-2')>0 )
    return 'PINNACLE SC200';
  elseif ( strpos($_SERVER['HTTP_USER_AGENT'],'-PIN-')>0 )
    return 'PINNACLE';
  elseif ( strpos($_SERVER['HTTP_USER_AGENT'],'-IOD-')>0 )
    return 'IO-DATA';
  elseif ( strpos($_SERVER['HTTP_USER_AGENT'],'-LTI-')>0 )
    return 'BUFFALO';
  elseif ( strpos($_SERVER['HTTP_USER_AGENT'],'-MMS-')>0 )
    return 'MOMITSU';
  elseif ( strpos($_SERVER['HTTP_USER_AGENT'],'-ADS-')>0 )
    return 'ADSTECH';
  elseif ( strpos($_SERVER['HTTP_USER_AGENT'],'-FIA-')>0 )
    return 'FIA';
  elseif ( strpos($_SERVER['HTTP_USER_AGENT'],'-VNE-')>0 )
    return 'SNAZIO';
  elseif ( strpos($_SERVER['HTTP_USER_AGENT'],'-HNB-')>0 )
    return 'H&B';
  elseif ( strpos($_SERVER['HTTP_USER_AGENT'],'-EGT-')>0 )
    return 'ELGATO';
  elseif ( strpos($_SERVER['HTTP_USER_AGENT'],'MSIE')>0 )
    return 'PC';
  else
    return 'UNKNOWN';
}

function is_hardware_player()
{ return get_player_type() != "PC"; }

function is_pc()
{ return get_player_type() == "PC"; }

#-------------------------------------------------------------------------------------------------
# Determine screen type (currently only PAL or NTSC - no support for HDTV).
#-------------------------------------------------------------------------------------------------

function get_screen_type()
{  
  if ( !isset($_SESSION["display_type"]) )
  {
    if (is_hardware_player())
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
# Maximum size playlist that the hardware players can accept.
#-------------------------------------------------------------------------------------------------

function max_playlist_size()
{
  return 2000;
}

#-------------------------------------------------------------------------------------------------
# Returns the TVID code that the player accepts for the given "code". This is useful as some
# players (such as the showcenter 200) have different TVID codes for the same RC button.
#-------------------------------------------------------------------------------------------------

function tvid( $code )
{
  // Define empty array
  $map = array();
  $code = strtoupper($code);

  // Depending on hardware player, override default values by storing in the $map array
  switch ( get_player_type() )
  {
    case 'PINNACLE SC200':
          $map = array( 'KEY_A'     => 'A'
                      , 'KEY_B'     => 'B'
                      , 'KEY_C'     => 'C' );
          break;
          
    case 'IO-DATA':
          $map = array( 'BACKSPACE' => 'back'
                      , 'KEY_A'     => 'play'
                      , 'KEY_B'     => 'esc'
                      , 'KEY_C'     => 'repeat' );
         break;
         
    case 'ELGATO':
    case 'NEUSTON':
          $map = array( 'KEY_A'     => 'red'
                      , 'KEY_B'     => 'green'
                      , 'KEY_C'     => 'blue' );
         break;
  }

  
  // Return the appropriate TVID html code.
  if (array_key_exists($code,$map))
    return ' TVID="'.$map[$code].'" ';
  else 
    return ' TVID="'.$code.'" ';
}

#-------------------------------------------------------------------------------------------------
# Returns the correct STYLE tag to use to display a link to the quick-access buttons (eg: ABC)
#-------------------------------------------------------------------------------------------------

function quick_access_img( $position )
{
  switch ( get_player_type() )
  {
    case 'ELGATO':
    case 'NEUSTON':
         $map = array('IMG_RED','IMG_GREEN','IMG_BLUE');
         break;    

    case 'IO-DATA':
         $map = array('IMG_PAUSE','IMG_STOP','IMG_REPEAT');
         break;

    default:
         $map = array('IMG_A','IMG_B','IMG_C');
  }

  if (isset($map[$position]))
    return $map[$position];
  else 
    return false;
}

#-------------------------------------------------------------------------------------------------
# Returns an array of file extensions that are supported by the hardware player.
#-------------------------------------------------------------------------------------------------

function media_exts_movies()
{
  return explode(',' ,'avi,mpg,mpeg,vob,wmv,asf');
}

function media_exts_music()
{
  return explode(',' ,'mp3,wma');
}

function media_exts_photos()
{
  return explode(',' ,'jpeg,jpg,gif');
}

#-------------------------------------------------------------------------------------------------
#-------------------------------------------------------------------------------------------------

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
