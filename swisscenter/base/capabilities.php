<?php
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor
 *************************************************************************************************/

require_once( realpath(dirname(__FILE__).'/server.php'));
require_once( realpath(dirname(__FILE__).'/utils.php'));
require_once( realpath(dirname(__FILE__).'/screen.php'));
require_once( realpath(dirname(__FILE__).'/settings.php'));
require_once( realpath(dirname(__FILE__).'/prefs.php'));
require_once( realpath(dirname(__FILE__).'/mysql.php'));

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
    elseif ( strpos($_SERVER['HTTP_USER_AGENT'],'MSIE')!== false ) // Browser
      $type = 'PC';
    elseif ( strpos($_SERVER['HTTP_USER_AGENT'],'Mozilla')!== false ) // Browser
      $type = 'PC';
    elseif ( strpos($_SERVER['HTTP_USER_AGENT'],'Opera')!== false ) // Browser
      $type = 'PC';
    elseif ( strpos($_SERVER['HTTP_USER_AGENT'],'Wget')!== false ) // Browser
      $type = 'PC';
    else
      $type = get_player_make().'-'.get_player_model(); // Media player

    $_SESSION["device"]["device_type"]  = $type;
    $_SESSION["device"]["agent_string"] = $_SERVER['HTTP_USER_AGENT'];
    return $type;
  }
}

function get_player_make()
{
  $player_make = preg_get('/\d+-\d+-\d+-\d+-(.*)-\d+-/U', $_SESSION["device"]["box_id"]);
  return empty($player_make) ? 'ZZZ' : $player_make;
}

function get_player_model()
{
  $player_model = preg_get('/\d+-\d+-\d+-\d+-.*-(\d+)-/U', $_SESSION["device"]["box_id"]);
  return empty($player_model) ? '000' : $player_model;
}

function get_player_chipset()
{
  return db_value("select chipset from client_profiles where make='".get_player_make()."' and model=".get_player_model());
}

function is_hardware_player()
{ return get_player_type() != "PC"; }

function is_pc()
{ return get_player_type() == "PC"; }

function get_player_firmware_datestr()
{
  if (is_hardware_player())
    return preg_get('/([0-9]{6})/', $_SESSION["device"]["box_id"]);
  else
    return false;
}

function get_player_firmware_date()
{
  if (is_hardware_player())
  {
    $datestr = get_player_firmware_datestr();
    return mktime(0,0,0,substr($datestr,2,2),substr($datestr,4,2),2000+substr($datestr,0,2));
  }
  else
    return false;
}

#-------------------------------------------------------------------------------------------------
# Returns an array of all Network Shares defined in attached NMT players
#-------------------------------------------------------------------------------------------------

function get_nmt_network_shares()
{
  // IP addresses of all connected NMT's
  $shares = array();
  $nmt = db_col_to_list("select ip_address from clients left join client_profiles on make=substring(device_type,1,3) and model=substring(device_type,5,3) where model >= 400");

  if (is_array($nmt) && count($nmt)>0)
  {
    foreach ( $nmt as $ip )
    {
      // Get Network Shares page from NMT
      if (socket_check($ip,8883,1))
        $html = @file_get_contents('http://'.$ip.':8883/network_share.html');
      else
        $html = '';

      // Identify defined Network Shares
      $matches = array();
      preg_match_all('/<td height="\d+" class="txt">.*&nbsp;(.*)<\/td>/',$html,$matches);
      for ($i = 0; $i<count($matches[1]); $i++)
      {
        $unc = array();
        switch ( true )
        {
          case strstr( $matches[1][$i], 'nfs' ):
            preg_match('/.*nfs:\/\/(.*)/', $matches[1][$i], $unc);
            $shares[] = array( 'path' => '[NFS] '.str_replace('/', ':', $unc[1]),
                               'name' => $matches[1][$i] );
            break;

          case strstr( $matches[1][$i], 'smb' ):
            preg_match('/.*smb:\/\/(.*)/', $matches[1][$i], $unc);
            $shares[] = array( 'path' => '[SMB] '.str_replace('/', ':', $unc[1]),
                               'name' => $matches[1][$i] );
            break;

          default:
            $shares[] = array( 'path' => 'NETWORK_SHARE/'.$matches[1][$i],
                               'name' => $matches[1][$i] );
            break;
        }
      }
    }
  }
  return $shares;
}

#-------------------------------------------------------------------------------------------------
# Maximum size playlist that the hardware players can accept.
#-------------------------------------------------------------------------------------------------

function max_playlist_size()
{
  return get_sys_pref('MAX_PLAYLIST_SIZE',200);
}

#-------------------------------------------------------------------------------------------------
# Returns the TVID code that the player accepts for the given "code". This is useful as some
# players (such as the showcenter 200) have different TVID codes for the same RC button.
#-------------------------------------------------------------------------------------------------

function tvid( $code )
{
  // Return the appropriate TVID html code.
  return ' TVID="'.get_tvid_pref( get_player_type(), $code ).'" ';
}

function tvid_code( $code )
{
  // Return the appropriate TVID code.
  return get_tvid_pref( get_player_type(), $code );
}

#-------------------------------------------------------------------------------------------------
# Returns the correct STYLE tag to use to display a link to the quick-access buttons (eg: ABC)
#-------------------------------------------------------------------------------------------------

function quick_access_img( $position )
{
  switch ( get_player_make() )
  {
    case 'IOD':
         $map = array('QUICK_PAUSE','QUICK_STOP','QUICK_REPEAT');
         break;
    case 'SYB':
         $map = array('QUICK_FAST_REWIND','QUICK_FAST_FORWARD','QUICK_NEXT');
         break;
    case 'NGR':
         $map = array('QUICK_NGR_A','QUICK_NGR_B','QUICK_NGR_C');
         break;
    case 'PIN':
         $map = array('QUICK_A','QUICK_B','QUICK_C');
         break;

    default:
         $map = array('QUICK_RED','QUICK_GREEN','QUICK_YELLOW','QUICK_BLUE');
  }

  if (isset($map[$position]))
    return $map[$position];
  else
    return 'QUICK_BLUE';
}

#-------------------------------------------------------------------------------------------------
# Returns an array of file extensions that are supported by the hardware player.
#-------------------------------------------------------------------------------------------------

function media_exts_playlists()
{
  return explode(',' ,'m3u,wpl');
}

function media_exts_movies()
{
  return explode(',' ,'asf,avi,dat,divx,flv,m2ts,m2v,m4v,mkv,mov,mp4,mpe,mpeg,mpg,ts,tp,vob,wmv,xvid,dvr-ms');
}

function media_exts_music()
{
  return explode(',' ,'aac,ac3,m4a,mp2,mp3,ogg,tta,wav,wma,flac');
}

function media_exts_photos()
{
  return explode(',' ,'bmp,jpeg,jpg,gif,png');
}

function media_exts_radio()
{
  return explode(',' ,'url');
}

function media_exts_web()
{
  return explode(',' ,'url');
}

function media_exts_dvd()
{
  return explode(',' ,'ifo,img,iso');
}

function media_ext_subtitles()
{
  return explode(',' ,'srt,sub,ssa,smi');
}

function media_exts( $media_type )
{
  switch ($media_type)
  {
    case MEDIA_TYPE_MUSIC : return media_exts_music();  break;
    case MEDIA_TYPE_PHOTO : return media_exts_photos(); break;
    case MEDIA_TYPE_VIDEO : return array_merge(media_exts_movies(), media_exts_dvd()); break;
    case MEDIA_TYPE_WEB   : return media_exts_web();    break;
    case MEDIA_TYPE_TV    : return media_exts_movies(); break;
  }

  // Should never happen
  return array();
}

function media_exts_with_GetID3_support()
{
  return explode(',' ,'aac,ac3,bmp,mp3,mp4,asf,riff,flac,flv,jpg,jpeg,gif,ogg,png,quicktime,matroska,mpeg,mpg,tta');
}

#-------------------------------------------------------------------------------------------------
# Returns an array of PHP modules that are required/suggested to be installed for this player type
#-------------------------------------------------------------------------------------------------

function get_required_modules_list()
{
  return explode(',','gd,json,mbstring,mysql,mysqli,xml,session');
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
  $resume = (db_value("select resume from client_profiles where make='".get_player_make()."' and model=".get_player_model()) == 'YES' );

  return ( $resume == 'YES' && version_compare(simese_version(),'1.36','>=') );
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
    $result = (db_value("select pod_sync from client_profiles where make='".get_player_make()."' and model=".get_player_model()) > 0 ? true : false);
  }

  return $result;
}

#-------------------------------------------------------------------------------------------------
# Returns the POD (Picture On Demand) parameter to pass to the hardware player that controls how
# the images and music with by synchronized together.
#
# On the showcenter, the values are:
#   1 - FF/RW buttons control the photos. Music playback is unaffected
#   2 - FF/RW buttons control the music. Photo playback is unaffected (or so Pinnacle claim)
#   3 - FF/RW controls both the photos and the music.
# On the EVA700, the values are:
#   1 - FF/RW buttons control the photos. Music playback is unaffected
#   2 - FF/RW buttons control the music. Image is ID3 tag CoverArt
#   3 - FF/RW controls both the photos and the music. RW will goto prev track but photo will always advance!
#-------------------------------------------------------------------------------------------------

function now_playing_sync_type()
{
  switch ( get_sys_pref('NOW_PLAYING_STYLE', 'ORIGINAL') )
  {
    case 'ORIGINAL':
      // Images sync'ed with tracks
      if ( !($result = db_value("select pod_sync from client_profiles where make='".get_player_make()."' and model=".get_player_model())) )
        $result = 3;  // Default values for players unless we discover otherwise.
      break;

    case 'ENHANCED':
      // Images constantly refreshed to update progress bar
      if ( !($result = db_value("select pod_no_sync from client_profiles where make='".get_player_make()."' and model=".get_player_model())) )
        $result = 2;  // Default values for players unless we discover otherwise.
      break;
  }

  return $result;
}

#-------------------------------------------------------------------------------------------------
# Returns the POD (Picture On Demand) parameter to pass to the hardware player that controls how
# an audio stream with images will be synchronized together.
#-------------------------------------------------------------------------------------------------

function stream_sync_type()
{
  if ( $result = db_value("select pod_stream from client_profiles where make='".get_player_make()."' and model=".get_player_model()) )
    return $result;
  else
    return 1;  // Default values for players unless we discover otherwise.
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
  if ( $result = db_value("select transition from client_profiles where make='".get_player_make()."' and model=".get_player_model()) )
    return $result;
  else
    return 0;  // Default values for players unless we discover otherwise.
}

#-------------------------------------------------------------------------------------------------
# This function returns a font size multiplier based on both the hardware player in use and the
# screen resolution. The values in this function have been submitted by users to the forums for
# what looks best on their devices.
#-------------------------------------------------------------------------------------------------

function player_fontsize_multiplier()
{
  if ( is_pc() )
  {
    // Return the multiplier for PC browser or the currently set override.
    return get_sys_pref('FONTWIDTH_MULTIPLIER_PC',1.0);
  }
  else
  {
    // Return the multiplier for specific player or the currently set override.
    $player_model = get_player_model();
    switch ( true )
    {
      case ( $player_model > 400 ):
        return get_sys_pref('FONTWIDTH_MULTIPLIER_400',1.35); break;
      case ( $player_model > 200 ):
        return get_sys_pref('FONTWIDTH_MULTIPLIER_200',1.2); break;
      case ( $player_model > 100 ):
        return get_sys_pref('FONTWIDTH_MULTIPLIER_100',1.2); break;
      default:
        // Unknown player, so return the default (1) or the currently set override.
        return get_sys_pref('FONTWIDTH_MULTIPLIER',1.0); break;
    }
  }
}

//-------------------------------------------------------------------------------------------------
// Import the players config XML
//-------------------------------------------------------------------------------------------------

function load_players_config()
{
  $players_file = SC_LOCATION."config/config_players.xml";

  if (file_exists($players_file))
  {
    @set_magic_quotes_runtime(0);

    // Read and process XML file
    $data = file_get_contents($players_file);
    if ($data !== false)
    {
      // Parse the players config XML file
      preg_match_all('/<player name="(.*)" make="(.*)" model="(.*)" chipset="(.*)" resume="(.*)" pod_sync="(.*)" pod_no_sync="(.*)" pod_stream="(.*)" transition="(.*)"/U', $data, $matches);

      if (count($matches[0]) == 0)
        send_to_log(2,'Parsing '.$players_file.' failed to find any players!');
      else
      {
        db_sqlcommand("delete from client_profiles");
        foreach ($matches[1] as $index=>$name)
        {
          $player = array();
          $player["name"]        = $name;
          $player["make"]        = $matches[2][$index];
          $player["model"]       = $matches[3][$index];
          $player["chipset"]     = $matches[4][$index];
          $player["resume"]      = $matches[5][$index];
          $player["pod_sync"]    = $matches[6][$index];
          $player["pod_no_sync"] = $matches[7][$index];
          $player["pod_stream"]  = $matches[8][$index];
          $player["transition"]  = $matches[9][$index];

          // Insert the row into the database
          send_to_log(5,'Adding player   : '.$name);
          db_insert_row( "client_profiles", $player);
        }
      }
    }
    else
      send_to_log(6,"Unable to load players config file: $players_file");
  }
}

//-------------------------------------------------------------------------------------------------
// Export the players config to XML.
//-------------------------------------------------------------------------------------------------

function save_players_config()
{
  $players_file = SC_LOCATION."config/config_players.xml";

  $data = db_toarray('select * from client_profiles order by model, name');

  $xml = new XmlBuilder();
  $xml->Push('swisscenter');
  $xml->Push('players');

  foreach ($data as $player)
  {
    $xml->Push('player', array('name'        => utf8_encode(trim($player["NAME"])),
                               'make'        => $player["MAKE"],
                               'model'       => $player["MODEL"],
                               'chipset'     => $player["CHIPSET"],
                               'resume'      => $player["RESUME"],
                               'pod_sync'    => $player["POD_SYNC"],
                               'pod_no_sync' => $player["POD_NO_SYNC"],
                               'pod_stream'  => $player["POD_STREAM"],
                               'transition'  => $player["TRANSITION"]));
    $xml->Pop('player');
  }

  $xml->Pop('players');
  $xml->Pop('swisscenter');

  if ($fsp = fopen($players_file, 'wb'))
  {
    fwrite($fsp, $xml->getXml());
    fclose($fsp);
    send_to_log(6,"Saved players config: $players_file");
  }
  else
    send_to_log(6,"Failed to save players config file: $players_file");
}

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
