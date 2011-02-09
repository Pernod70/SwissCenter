<?php
/**************************************************************************************************
   SWISScenter Source                                                              Nigel Barnes
 *************************************************************************************************/

  require_once( realpath(dirname(__FILE__).'/base/session.php'));
  require_once( realpath(dirname(__FILE__).'/base/image_screens.php'));
  require_once( realpath(dirname(__FILE__).'/base/capabilities.php'));
  require_once( realpath(dirname(__FILE__).'/ext/iradio/iradio_playing.php'));

  // Log details of the playlist request
  send_to_log(1,"------------------------------------------------------------------------------");
  send_to_log(1,"Playlist Requested : ".current_url()." by client (".client_ip().")");

  $server  = server_address();
  $station = un_magic_quote($_REQUEST["station"]);

  // Determine type of radio stream being played
  if ( strpos($_REQUEST["url"],'live365') > 0 )
  {
    // Determine broadcaster for Live365 station
    $broadcaster = preg_get('/broadcaster=(.*)&/U', un_magic_quote($_REQUEST["url"]));

    $url = $server."music_radio_image.php?".current_session()."&live365=".$broadcaster.
                   "&station=".urlencode($station)."&x=.jpg";
  }
  else
  {
    // Determine host and port for SHOUTcast server
    $IP_Port = array();
    if ( strpos($_REQUEST["url"],'http')===0 && (empty($_REQUEST["host"]) || empty($_REQUEST["port"])) )
    {
      // Retrieve server details from playlist (pls) (3 attempts)
      $i=0;
      $info = '';
      send_to_log(6,'Attempting to obtain SHOUTcast server details from playlist');
      while ( $i < 3 && empty($info) )
      {
        if ( ($info = file_get_contents(un_magic_quote($_REQUEST["url"]))) == false )
          send_to_log(6,'- Attempt '.$i.': Failed to download playlist');
        elseif ( preg_match('/(\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}):(\d{1,5})/', $info, $IP_Port) == 0 )
          send_to_log(6,'- Failed to find SHOUTcast server in playlist:', $info);
        else
          send_to_log(6,'- SHOUTcast server found at '.$IP_Port[1].':'.$IP_Port[2]);
        $i++;
      }

      $url = $server."music_radio_image.php?".current_session()."&schost=".$IP_Port[1]."&scport=".$IP_Port[2].
                     "&station=".urlencode($station)."&x=.jpg";
    }
  }

  // List of images to display to the user (changes every 30 seconds)
  $transition = now_playing_transition();
  $refresh    = get_sys_pref("NOW_PLAYING_REFRESH_INTERVAL",20);

  // Clear the Now Playing details
  $_SESSION["now_playing"] = '';

  echo "$refresh|$transition| |$url|\n";
  echo "$refresh|$transition| |$url|\n";

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
