<?php
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor
 *************************************************************************************************/

  require_once( realpath(dirname(__FILE__).'/base/image_screens.php'));
  require_once( realpath(dirname(__FILE__).'/base/capabilities.php'));

  /**
   * Returns the track title currently being played by the ShoutCast server.
   *
   * @param integer $host
   * @param integer $port
   * @return string
   */
  function shoutcast_now_playing($host, $port)
  {
    send_to_log(5,'Connecting to ShoutCast server '.$host.':'.$port);
    $fp = @fsockopen($host,$port,$errno,$errstr,10);
    if (!$fp) 
    { 
      send_to_log(5,'- Connection refused'); // Displays when sever is offline
    } 
    else
    { 
      fputs($fp, "GET /7.html HTTP/1.0\r\nUser-Agent: Mozilla\r\n\r\n");
      while (!feof($fp)) 
      {
        $info = fgets($fp);
      }
      $info = str_replace('</body></html>', "", $info);
      $split = explode(',', $info);
      if (empty($split[6]) )
      {
        send_to_log(5,'- The current song is not available'); // Displays when server is online but no song title
      }
      else
      {
        $title = str_replace('\'', '`', $split[6]);
        $title = str_replace(',', ' ', $title);
        send_to_log(5,'- Now playing "'.$title.'"'); 
        return $title; // Displays song
      }
    }
    return false;
  }

  if ( strpos($_REQUEST["playlist"],'http')===0 && (empty($_REQUEST["host"]) || empty($_REQUEST["port"])) )
  {
    // Retrieve server details from playlist (pls) (3 attempts)
    $i=0;
    send_to_log(6,'Attempting to obtain server details from radio playlist'); 
    while ( $i < 3 && empty($info) )
    {
      if ( ($info = file_get_contents(un_magic_quote($_REQUEST["playlist"]))) == false )
        send_to_log(6,'- Attempt '.$i.': Failed to download playlist');
      elseif ( preg_match('/(\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}):(\d{1,5})/', $info, $host) == 0 )
        send_to_log(6,'- Failed to find internet radio server in playlist:', $info);
      else
        send_to_log(6,'- Radio server found at '.$host[1].':'.$host[2]);
      $i++;
    }
  }
  else
  {
    $host[1] = $_REQUEST["host"];
    $host[2] = $_REQUEST["port"];
  }
  
  if ( isset($_REQUEST["list"]))
  {
    // List of images to display to the user (changes every 30 seconds)
    $server     = server_address();
    $station    = un_magic_quote($_REQUEST["station"]);
    $url        = $server."music_radio_image.php?".current_session()."&host=".$host[1]."&port=".$host[2].
                          "&station=".urlencode($station)."&x=.jpg";
    $transition = now_playing_transition();

    // Clear the Now Playing details
    $_SESSION["now_playing"] = '';

    echo "30|$transition| |$url|\n";
    echo "30|$transition| |$url|\n";
  }
  else
  {
    // Get now playing details
    $playing = shoutcast_now_playing($host[1], $host[2]);
    
    // Generate and display the "Now Playing" screen.
    // - If EVA700 then only send a new image if the details have changed. Avoids continuous refreshing.
    if (get_player_type()!=='NETGEAR' || $_SESSION["now_playing"]!==$playing )
    {
      $_SESSION["now_playing"] = $playing;
      $station = un_magic_quote($_REQUEST["station"]);

      // Search for a defined image for this station
      $logo = '';
      $station_logos = db_toarray("select lower(station) station, image from iradio_stations");
      foreach ($station_logos as $station_logo)
      {
        if (strpos(str_replace(' ','',strtolower($station)), str_replace(' ','',$station_logo["STATION"])) !== false)
        {
          $logo = $station_logo["IMAGE"];
          break;
        }
      }

      $image = station_playing_image($station, $playing, $logo);
      $image->output('jpeg');
    }
  }

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>