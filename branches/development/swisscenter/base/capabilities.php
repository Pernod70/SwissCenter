<?
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor
 *************************************************************************************************/

require_once( realpath(dirname(__FILE__).'/server.php'));
require_once( realpath(dirname(__FILE__).'/utils.php'));
require_once( realpath(dirname(__FILE__).'/screen.php'));

#-------------------------------------------------------------------------------------------------
# Returns the type of hardware player that the SwissCenter is communicating with.
#
# NOTE: If the player type has already been determined and is stored in the session, then we use
#       that instead. This is a workaround for the fact that the sigma designs hardware doesn't 
#       send a User Agent String when sending GET requests for playlists and media content.
#-------------------------------------------------------------------------------------------------

function get_player_type()
{
  if (isset($_SESSION["device"]["device_type"]))
    return $_SESSION["device"]["device_type"];
  else 
  {
    if     ( strpos($_SERVER['HTTP_USER_AGENT'],'-NST-')>0 )
      $type = 'NEUSTON';
    elseif ( strpos($_SERVER['HTTP_USER_AGENT'],'-PIN-2')>0 )
      $type = 'PINNACLE SC200';
    elseif ( strpos($_SERVER['HTTP_USER_AGENT'],'-PIN-')>0 )
      $type = 'PINNACLE';
    elseif ( strpos($_SERVER['HTTP_USER_AGENT'],'-IOD-')>0 )
      $type = 'IO-DATA';
    elseif ( strpos($_SERVER['HTTP_USER_AGENT'],'-LTI-')>0 )
      $type = 'BUFFALO';
    elseif ( strpos($_SERVER['HTTP_USER_AGENT'],'-MMS-')>0 )
      $type = 'MOMITSU';
    elseif ( strpos($_SERVER['HTTP_USER_AGENT'],'-ADS-')>0 )
      $type = 'ADSTECH';
    elseif ( strpos($_SERVER['HTTP_USER_AGENT'],'-FIA-')>0 )
      $type = 'FIA';
    elseif ( strpos($_SERVER['HTTP_USER_AGENT'],'-VNE-')>0 )
      $type = 'SNAZIO';
    elseif ( strpos($_SERVER['HTTP_USER_AGENT'],'-HNB-')>0 )
      $type = 'H&B';
    elseif ( strpos($_SERVER['HTTP_USER_AGENT'],'-EGT-')>0 )
      $type = 'ELGATO';
    elseif ( strpos($_SERVER['HTTP_USER_AGENT'],'MSIE')>0 )
      $type = 'PC';
    elseif ( strpos($_SERVER['HTTP_USER_AGENT'],'Mozilla')>0 )
      $type = 'PC';
    else
      $type = 'UNKNOWN';

    $_SESSION["device"]["device_type"]  = $type;
    $_SESSION["device"]["agent_string"] = $_SERVER['HTTP_USER_AGENT'];
    return $type;
  }
}

function is_hardware_player()
{ return get_player_type() != "PC"; }

function is_pc()
{ return get_player_type() == "PC"; }

#-------------------------------------------------------------------------------------------------
# Determine screen type (currently only PAL or NTSC - no support for HDTV).
#-------------------------------------------------------------------------------------------------

function OLD_get_screen_type()
{  
  if ( !isset($_SESSION["display_type"]) )
  {
    if (is_hardware_player())
    {
      $text = @file_get_contents('http://'.client_ip().':2020/readsyb_options_page.cgi');  
      send_to_log("Player has identified it's options as: ",$text);
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

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
