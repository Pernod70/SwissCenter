<?php
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor
 *************************************************************************************************/

  require_once( realpath(dirname(__FILE__).'/base/session.php'));
  require_once( realpath(dirname(__FILE__).'/base/image_screens.php'));
  require_once( realpath(dirname(__FILE__).'/base/playlist.php'));

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

  $tracks    = get_tracklist();
  $prev_info = array();
  $next_info = array();

  // If the hardware player doesn't support a "Now Playing" screen, then we try to fake one by
  // looking in the session at the index of the last file streamed to the player.
  $idx = isset($_REQUEST["idx"]) ? $_REQUEST["idx"] : $_SESSION["LAST_RESPONSE_IDX"];

  // Get the previous and next track details if available.
  if ($idx > 0)
    $prev_info[0] = $tracks[$idx-1];
  if ($idx < count($tracks)-1)
    $next_info[0] = $tracks[$idx+1];

  // Generate and display the "Now Playing" screen.
  // - If EVA700 then only send a new image if the details have changed. Avoids continuous refreshing.
  if (get_player_make()!=='NGR' || $_SESSION["now_playing"]!==$tracks[$idx] || get_sys_pref('NOW_PLAYING_STYLE', 'ORIGINAL') == 'ENHANCED')
  {
    // Track changed so reset now playing timer
    if ($_SESSION["now_playing"]!==$tracks[$idx])
      $_SESSION["now_playing_start_time"] = time();

    $_SESSION["now_playing"] = $tracks[$idx];

    // Send any pending output (inc headers) and stop timeouts or user aborts killing the script
    ob_end_flush();
    ignore_user_abort(TRUE);
    session_write_close();
    @set_time_limit(0);

    if ( get_sys_pref('NOW_PLAYING_STYLE', 'ORIGINAL') == 'ENHANCED' )
    {
      // Percentage to show on progress bar (based upon time since track started)
      $percent_played = min((time() - $_SESSION["now_playing_start_time"]) / $tracks[$idx]["LENGTH"], 1);
      $image = now_playing_image_fanart( $tracks[$idx], $prev_info, $next_info, ($idx+1).' / '.count($tracks), $percent_played );
    }
    else
    {
      $photos = (internet_available() && get_user_pref('LASTFM_IMAGES','YES') == 'YES') ? get_lastfm_artist_images( $tracks[$idx]["ARTIST"], 'large' ) : '';
      $image = now_playing_image( $tracks[$idx], $prev_info, $next_info, ($idx+1).' / '.count($tracks), $photos );
    }

    $image->output('jpeg');
  }

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
