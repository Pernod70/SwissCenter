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
    $info = "";
    $songs = array();

    // Retrieve the HTML page detailing the recently played songs from the server

    send_to_log(5,'Connecting to ShoutCast server '.$host.':'.$port);
    if (($fp = @fsockopen($host,$port,$errno,$errstr,10)) === FALSE)
    {
      send_to_log(2,'- Connection refused to ShoutCast server',array("Host"=>$host,"Port"=>$port));
      return false;
    }
    else
    {
      fputs($fp, "GET /played.html HTTP/1.0\r\nUser-Agent: Mozilla\r\n\r\n");
      while (!feof($fp))
        $info .= fgets($fp);
    }

    // Process the results into an array

    $info = explode('<td>',$info);
    if (count($info)<4)
    {
      send_to_log(5,'- The current song is not available');
      return false;
    }
    else
    {
      for ($i=(count($info)-6)/2; $i>0; $i--)
        array_unshift($songs, strip_tags($info[$i*2+6]));

      array_unshift($songs,$info[4]);
    }

    // Return the results

    send_to_log(8,'Songs returned from the Shoutcast server',$songs);
    return $songs[0].'<>'.$songs[1].'<>'.$songs[2];
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
    $refresh    = get_sys_pref("NOW_PLAYING_REFRESH_INTERVAL",20);

    // Clear the Now Playing details
    $_SESSION["now_playing"] = '';

    echo "$refresh|$transition| |$url|\n";
    echo "$refresh|$transition| |$url|\n";
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
