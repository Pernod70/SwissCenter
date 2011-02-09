<?php
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor
 *************************************************************************************************/

  require_once( realpath(dirname(__FILE__).'/base/session.php'));
  require_once( realpath(dirname(__FILE__).'/base/image_screens.php'));
  require_once( realpath(dirname(__FILE__).'/base/capabilities.php'));
  require_once( realpath(dirname(__FILE__).'/ext/iradio/iradio_playing.php'));

  // Log details of the image request
  send_to_log(1,"------------------------------------------------------------------------------");
  send_to_log(1,"Image Requested : ".current_url()." by client (".client_ip().")");

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
    $playing[0]["STATION"]   = un_magic_quote($_REQUEST["station"]);

    // Determine image to display for this track
    if ( !isset($playing[0]["ALBUMART"]) )
    {
      // Get an artist image from Last.fm
      if ( ($playing[0]["ALBUMART"] = get_lastfm_artist_image($playing[0]["ARTIST"])) == false )
      {
        // No artist image so do we have a station image defined?
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

    $image = now_playing_image($playing[0], array_slice($playing, 1));
    $image->output('jpeg');
  }

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
