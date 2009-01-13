<?php
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor
 *************************************************************************************************/

  require_once( realpath(dirname(__FILE__).'/base/image_screens.php'));

  $tracks    = get_tracklist();
  $idx       = $_REQUEST["idx"];
  $prev_info = array();
  $next_info = array();

  // If the hardware player doesn't support a "Now Playing" screen, then we try to fake one by
  // looking in the session at the index of the last file streamed to the player.
  if (!support_now_playing())
    $idx = $_SESSION["LAST_RESPONSE_IDX"];
  
  // Get the previous and next track details if available.
  if ($idx > 0)
    $prev_info = $tracks[$idx-1];
  if ($idx < count($tracks)-1)
    $next_info = $tracks[$idx+1];
    
  // Generate and display the "Now Playing" screen.
  // - If EVA700 then only send a new image if the details have changed. Avoids continuous refreshing.
  if (get_player_type()!=='NETGEAR' || $_SESSION["now_playing"]!==$tracks[$idx])
  {
    $_SESSION["now_playing"] = $tracks[$idx];
    $image = now_playing_image( $tracks[$idx], $prev_info, $next_info, ($idx+1).' / '.count($tracks) );
    $image->output('jpeg');
  }

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
