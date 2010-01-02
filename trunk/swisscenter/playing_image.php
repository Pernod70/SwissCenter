<?php
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor
 *************************************************************************************************/

  require_once( realpath(dirname(__FILE__).'/base/image_screens.php'));

  $tracks    = get_tracklist();
  $prev_info = array();
  $next_info = array();

  // If the hardware player doesn't support a "Now Playing" screen, then we try to fake one by
  // looking in the session at the index of the last file streamed to the player.
  $idx = isset($_REQUEST["idx"]) ? $_REQUEST["idx"] : $_SESSION["LAST_RESPONSE_IDX"];

  // Get the previous and next track details if available.
  if ($idx > 0)
    $prev_info = $tracks[$idx-1];
  if ($idx < count($tracks)-1)
    $next_info = $tracks[$idx+1];

  // Generate and display the "Now Playing" screen.
  // - If EVA700 then only send a new image if the details have changed. Avoids continuous refreshing.
  if (get_player_make()!=='NGR' || $_SESSION["now_playing"]!==$tracks[$idx] || get_sys_pref('NOW_PLAYING_STYLE', 'ORIGINAL') == 'ENHANCED')
  {
    // Track changed so reset now playing timer
    if ($_SESSION["now_playing"]!==$tracks[$idx])
      $_SESSION["now_playing_start_time"] = time();

    $_SESSION["now_playing"] = $tracks[$idx];
    if ( get_sys_pref('NOW_PLAYING_STYLE', 'ORIGINAL') == 'ENHANCED' )
    {
      // Percentage to show on progress bar (based upon time since track started)
      $percent_played = min((time() - $_SESSION["now_playing_start_time"]) / $tracks[$idx]["LENGTH"], 1);
      $image = now_playing_image_fanart( $tracks[$idx], $prev_info, $next_info, ($idx+1).' / '.count($tracks), $percent_played );
    }
    else
      $image = now_playing_image( $tracks[$idx], $prev_info, $next_info, ($idx+1).' / '.count($tracks) );

    $image->output('jpeg');
  }

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
