<?php
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor
 *************************************************************************************************/

require_once( realpath(dirname(__FILE__).'/server.php'));
require_once( realpath(dirname(__FILE__).'/utils.php'));
require_once( realpath(dirname(__FILE__).'/screen.php'));
require_once( realpath(dirname(__FILE__).'/settings.php'));
require_once( realpath(dirname(__FILE__).'/prefs.php'));

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
    if (!key_exists('HTTP_USER_AGENT',$_SERVER))
      $type = 'UNKNOWN';
    elseif ( strpos($_SERVER['HTTP_USER_AGENT'],'-NST-')!== false )
      $type = 'NEUSTON';
    elseif ( strpos($_SERVER['HTTP_USER_AGENT'],'-PIN-2')!== false )
      $type = 'PINNACLE SC200';
    elseif ( strpos($_SERVER['HTTP_USER_AGENT'],'-PIN-')!== false )
      $type = 'PINNACLE';
    elseif ( strpos($_SERVER['HTTP_USER_AGENT'],'-IOD-')!== false )
      $type = 'IO-DATA';
    elseif ( strpos($_SERVER['HTTP_USER_AGENT'],'-LTI-')!== false )
      $type = 'BUFFALO';
    elseif ( strpos($_SERVER['HTTP_USER_AGENT'],'-MMS-')!== false )
      $type = 'MOMITSU';
    elseif ( strpos($_SERVER['HTTP_USER_AGENT'],'-ADS-')!== false )
      $type = 'ADSTECH';
    elseif ( strpos($_SERVER['HTTP_USER_AGENT'],'-FIA-')!== false )
      $type = 'FIA';
    elseif ( strpos($_SERVER['HTTP_USER_AGENT'],'-VNE-')!== false )
      $type = 'SNAZIO';
    elseif ( strpos($_SERVER['HTTP_USER_AGENT'],'-HNB-')!== false )
      $type = 'H&B';
    elseif ( strpos($_SERVER['HTTP_USER_AGENT'],'-EGT-')!== false )
      $type = 'ELGATO';
    elseif ( strpos($_SERVER['HTTP_USER_AGENT'],'-NGR-')!== false )
      $type = 'NETGEAR';
    elseif ( strpos($_SERVER['HTTP_USER_AGENT'],'-SYB-')!== false )
      $type = 'SYABAS';
    elseif ( strpos($_SERVER['HTTP_USER_AGENT'],'MSIE')!== false )
      $type = 'PC';
    elseif ( strpos($_SERVER['HTTP_USER_AGENT'],'Mozilla')!== false )
      $type = 'PC';
    elseif ( strpos($_SERVER['HTTP_USER_AGENT'],'Opera')!== false )
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
          
    case 'H&B':
          $map = array( 'BACKSPACE' => 'clear'
                      , 'KEY_A'     => 'red'
                      , 'KEY_B'     => 'green'
                      , 'KEY_C'     => 'blue' );
         break;
         
    case 'IO-DATA':
          $map = array( 'BACKSPACE' => 'back'
                      , 'KEY_A'     => 'play'
                      , 'KEY_B'     => 'esc'
                      , 'KEY_C'     => 'repeat' );
         break;
         
    case 'ELGATO':
    case 'BUFFALO':
    case 'NEUSTON':
          $map = array( 'KEY_A'     => 'red'
                      , 'KEY_B'     => 'green'
                      , 'KEY_C'     => 'blue' );
         break;
         
    case 'SYABAS':
          $map = array( 'BACKSPACE' => 'back'
                      , 'KEY_A'     => 'red'
                      , 'KEY_B'     => 'green'
                      , 'KEY_C'     => 'blue' );
          break;
         
    case 'NETGEAR':
          $map = array( 'KEY_A'     => 'play'
                      , 'KEY_B'     => 'setup'
                      , 'KEY_C'     => 'blue'
                      , 'MUSIC'     => 'green'
                      , 'MOVIE'     => 'red'
                      , 'PHOTO'     => 'yellow' );
          break;
  }

  // Get codes from system prefs that have been defined by user
  $tvid_prefs = db_toarray("select name, value from system_prefs where name like 'TVID_".get_player_type()."_%'");
  if (!empty($tvid_prefs))
    foreach ($tvid_prefs as $tvid)
      $map[substr($tvid['NAME'], strlen(get_player_type())+6)] = $tvid['VALUE'];

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
         $map = array('QUICK_RED','QUICK_GREEN','QUICK_BLUE');
         break;    
    case 'H&B':
         $map = array('QUICK_RED','QUICK_GREEN','QUICK_BLUE');
         break;    
    case 'IO-DATA':
         $map = array('QUICK_PAUSE','QUICK_STOP','QUICK_REPEAT');
         break;
    case 'SYABAS':
         $map = array('QUICK_FAST_REWIND','QUICK_FAST_FORWARD','QUICK_NEXT');
         break;
    case 'NETGEAR':
         $map = array('QUICK_NGR_A','QUICK_NGR_B','QUICK_NGR_C');
         break;   

    default:
         $map = array('QUICK_A','QUICK_B','QUICK_C');
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
  return explode(',' ,'asf,avi,dat,divx,m2v,mpe,mpeg,mpg,ts,tp,vob,wmv,xvid,dvr-ms');
}

function media_exts_music()
{
  return explode(',' ,'ac3,mp2,mp3,ogg,wav,wma');
}

function media_exts_photos()
{
  return explode(',' ,'jpeg,jpg,gif,png');
}

function media_exts_radio()
{
  return explode(',' ,'url');
}

function media_exts_web()
{
  return explode(',' ,'url');
}

function media_exts( $media_type )
{
  switch ($media_type)
  {  
    case MEDIA_TYPE_MUSIC : return media_exts_music();  break;
    case MEDIA_TYPE_PHOTO : return media_exts_photos(); break;
    case MEDIA_TYPE_VIDEO : return media_exts_movies(); break;
    case MEDIA_TYPE_WEB   : return media_exts_web();    break;
    case MEDIA_TYPE_TV    : return media_exts_movies(); break;
  }
  
  // Should never happen
  return array();
}

#-------------------------------------------------------------------------------------------------
# Returns an array of PHP modules that are required/suggested to be installed for this player type
#-------------------------------------------------------------------------------------------------

function get_required_modules_list()
{
  return explode(',','gd,mbstring,mysql,xml,session');
}

function get_suggested_modules_list()
{
  return explode(',','zip');
}

#-------------------------------------------------------------------------------------------------
# Accesses the players's internal webserver to set the location of the progress bar for the
# "Now Playing" screen
#-------------------------------------------------------------------------------------------------

function set_progress_bar_location( $x, $y )
{
  $dummy = @file_get_contents('http://'.client_ip().':2020/pod_audio_info.cgi?x='
           .convert_x($x,SCREEN_COORDS).'&y='.convert_y($y,SCREEN_COORDS));  
}

#-------------------------------------------------------------------------------------------------
# Is this hardware device / server combination able to support particular features?
#-------------------------------------------------------------------------------------------------

function support_resume()
{
  $result = false;
  
  switch ( get_player_type() )
  {
    case 'IO-DATA':
         $result = false;
         break;

    default:
         $result = ( is_hardware_player() && version_compare(simese_version(),'1.36','>=') );
         break;
  }

  return $result;
}

#-------------------------------------------------------------------------------------------------
# Is this hardware device capable of displaying sync'd "Now Playing" screens?
#-------------------------------------------------------------------------------------------------

function support_now_playing()
{ 
  $user_opt = get_sys_pref('SUPPORT_NOW_PLAYING','AUTO');
  $result   = ($user_opt == 'YES');
  
  if ($user_opt == 'AUTO')
  {
    switch ( get_player_type() )
    {
      case 'BUFFALO':
      case 'IO-DATA':
      case 'SYABAS':
           $result = false;
           break;
      default:
           $result = true;
    }
  }

  return $result;
}

#-------------------------------------------------------------------------------------------------
# Returns the POD (Picture On Demand) parameter to pass to the hardware player that controls how
# the images and music with by synchronized together.
#
# On the showcenter, the values are:
#   1 - FF?RW buttons control the photos. Music playback is unaffected
#   2 - FF/RW buttons control the music. Photo playback is unaffected (or so Pinnacle claim)
#   3 - FF/RW controls both the photos and the music.
#-------------------------------------------------------------------------------------------------

function now_playing_sync_type()
{
  $result = 3;  // Default values for players unless we discover otherwise.
  
  switch ( get_player_type() )
  {
    case 'BUFFALO':
    case 'IO-DATA':
    case 'SYABAS':
         $result = 2;
         break;
  }

  return $result;  
}
  
#-------------------------------------------------------------------------------------------------
# The transition effect to use between "Now Playing" screens.
#
# Available effects are:
#  0 - Random                    1 - Wipe Down                 2 - Wipe Up
#  3 - Open Vertical             4 - Close Vertical            5 - Split Vertical 1
#  6 - Split Vertical 2          7 - Interlace                 8 - Fade to black
#
# NOTE: On the newer 2nd Gen machines, effect number 0 appears to be "no effect".
#-------------------------------------------------------------------------------------------------

function now_playing_transition()
{
  switch ( get_player_type() )
  {
    case 'IO-DATA':
    case 'PINNACLE': // Showcenter 1000
         return 8;
         break;

    default:
         return 0;
         break;
  }	
}

#-------------------------------------------------------------------------------------------------
# This function returns a font size multiplier based on both the hardware player in use and the
# screen resolution. The values in this function have been submitted by users to the forums for
# what looks best on their devices.
#-------------------------------------------------------------------------------------------------

function player_fontsize_multiplier()
{
  switch ( get_player_type().'/'.get_browser_size() )
  {
    case 'PC/800x450':                  return 2.2; break;
    case 'PINNACLE/624x496':            return 1.4; break;
  }	  
  
  // No-match, so return the default (1) or the currently set override.
  return get_sys_pref('FONTWIDTH_MULTIPLIER',1);
}

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
