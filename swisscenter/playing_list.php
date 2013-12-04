<?php
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor
 *************************************************************************************************/

  require_once( realpath(dirname(__FILE__).'/base/session.php'));
  require_once( realpath(dirname(__FILE__).'/base/capabilities.php'));
  require_once( realpath(dirname(__FILE__).'/base/mysql.php'));
  require_once( realpath(dirname(__FILE__).'/base/playlist.php'));

  // Log details of the playlist request
  send_to_log(0,"Playlist: ".$_SERVER["REQUEST_METHOD"]." ".current_url()." by client (".client_ip().")");

  $server     = server_address();
  $transition = now_playing_transition();
  $url        = $server."playing_image.php?".current_session();
  $data       = get_tracklist();
  $max_size   = max_playlist_size();

  // Clear the Now Playing details
  $_SESSION["now_playing"] = '';

  if (support_now_playing() && get_sys_pref('NOW_PLAYING_STYLE', 'ORIGINAL') == 'ORIGINAL' )
  {
    /**
     * The following code ouputs the list of photos that are sync'd with the music tracks
     * automatically by the player. There is no need whatsoever to keep refreshing the
     * screen, nor keep track of the media file that is currently playing.
     */

    $idx        = 0;
    $timeout    = 9999;
    send_to_log(7,"Support for sync'd music and photos enabled");
    send_to_log(7,'Generating List of "Now Playing" images');

    foreach ($data as $row)
    {
      if ($idx >= $max_size)
        break;

      send_to_log(7,' - '.$url."&idx=$idx&id=".$row["FILE_ID"]."&type=.jpg");
      echo "$timeout|$transition| |$url&idx=$idx&id=".$row["FILE_ID"]."&type=.jpg|\n";
      $idx++;
    }
  }
  else
  {
    /**
     * For players that do not have the capability to sync a photo list with the music, we
     * have to keep track of which track in the playlist is being streamed and refresh the
     * page frequently so that the information is kept up-to-date.
     */

    if (get_sys_pref('NOW_PLAYING_STYLE', 'ORIGINAL') == 'ORIGINAL')
      $timeout = get_sys_pref("NOW_PLAYING_REFRESH_INTERVAL",20);
    else
      $timeout = get_sys_pref("NOW_PLAYING_ENHANCED_REFRESH_INTERVAL",5);

    send_to_log(7,"No Support for sync'd music and photos.");

    echo "$timeout|$transition| |$url&type=.jpg|\n";
    echo "$timeout|$transition| |$url&type=.jpg|\n";

    // Reset IDX since the first image is requested before stream.php is able to set this.
    $_SESSION["LAST_RESPONSE_IDX"] = 0;
  }

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
