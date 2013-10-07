<?php
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor
 *************************************************************************************************/

  require_once( realpath(dirname(__FILE__).'/base/session.php'));
  require_once( realpath(dirname(__FILE__).'/base/image_screens.php'));
  require_once( realpath(dirname(__FILE__).'/base/capabilities.php'));
  require_once( realpath(dirname(__FILE__).'/resources/radio/iradio_playing.php'));

  // Log details of the image request
  send_to_log(0,"Image: ".$_SERVER["REQUEST_METHOD"]." ".current_url()." by client (".client_ip().")");

  // Send headers only if HEAD request
  if ( $_SERVER["REQUEST_METHOD"] == 'HEAD' )
  {
    header("Content-Type: image/jpeg");
    header("Connection: Keep-Alive");
    send_to_log(8,'HTTP Response Headers:',headers_list());
    exit;
  }

  // SHOUTcast reference
  $IP_Port[1]  = isset($_REQUEST["schost"]) ? $_REQUEST["schost"] : 0;
  $IP_Port[2]  = isset($_REQUEST["scport"]) ? $_REQUEST["scport"] : 0;

  // Live365 reference
  $broadcaster = isset($_REQUEST["live365"]) ? $_REQUEST["live365"] : '';

  // Get now playing details
  $iradio_playing = new iradio_playing();
  if (!empty($broadcaster))
    $playing = $iradio_playing->live365_played($broadcaster);
  elseif (!empty($IP_Port[1]) && !empty($IP_Port[2]))
    $playing = $iradio_playing->shoutcast_played($IP_Port[1], $IP_Port[2]);
  else
    $playing = array();

  // Generate and display the "Now Playing" screen.
  // - If EVA700 then only send a new image if the details have changed. Avoids continuous refreshing.
  if (get_player_make()!=='NGR' || $_SESSION["now_playing"]!==$playing[0] )
  {
    $_SESSION["now_playing"] = $playing[0];
    $playing[0]["STATION"]   = $_REQUEST["st"];

    // Send any pending output (inc headers) and stop timeouts or user aborts killing the script
    ob_end_flush();
    ignore_user_abort(TRUE);
    session_write_close();
    @set_time_limit(0);

    // Determine image to display for this track
    if ( !isset($playing[0]["ALBUMART"]) && !empty($playing[0]["ARTIST"]) && !empty($playing[0]["TITLE"]) )
    {
      // Get an album/artist image from Last.fm
      $track_info = lastfm_track_getInfo($playing[0]["ARTIST"], $playing[0]["TITLE"]);
      if ( isset($track_info["track"]["album"]["image"]) )
      {
        $image = array_last($track_info["track"]["album"]["image"]);
        $playing[0]["ALBUMART"] = $image["#text"];
      }
    }
    if ( !isset($playing[0]["ALBUMART"]) && !empty($playing[0]["ARTIST"]) )
    {
      // Get an artist image from Last.fm
      $image = get_lastfm_artist_image($playing[0]["ARTIST"]);
      if ( $image )
        $playing[0]["ALBUMART"] = $image;
    }
    if ( !isset($playing[0]["ALBUMART"]) )
    {
      // No artist image so do we have a station image defined?
      $logo = $_REQUEST["img"];
      if ( !empty($logo) )
      {
        $playing[0]["ALBUMART"] = $logo;
      }
      else
      {
        $station_logos = db_toarray("select lower(station) station, image from iradio_stations");
        foreach ($station_logos as $station_logo)
        {
          if (strpos(str_replace(' ','',strtolower($playing[0]["STATION"])), str_replace(' ','',$station_logo["STATION"])) !== false)
          {
            $playing[0]["ALBUMART"] = SC_LOCATION.'images/iradio/'.$station_logo["IMAGE"];
            break;
          }
        }
      }
    }

    $photos = get_lastfm_artist_images( $playing[0]["ARTIST"], 'large' );
    $image = now_playing_image($playing[0], array_slice($playing, 1), '', '', $photos);
    $image->output('jpeg');
  }

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
