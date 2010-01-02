<?php
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor
 *************************************************************************************************/

  require_once( realpath(dirname(__FILE__).'/base/server.php'));
  require_once( realpath(dirname(__FILE__).'/base/capabilities.php'));
  require_once( realpath(dirname(__FILE__).'/base/file.php'));

  // Generate the playlist based on the values passed as part of the request
  $video_ids  = explode(',', $_REQUEST["video_ids"]);
  $server     = server_address();
  $max_size   = max_playlist_size();
  $item_count = 0;

  send_to_log(7,'Generating list of YouTube videos to send to the networked media player.');

  foreach ($video_ids as $id)
  {
    if ($item_count >= $max_size)
      break;

    $url = $server.'stream_youtube.php?video_id='.$id.'&ext=.mp4';

    // Build up the playlist row to send to the player, including the title of the video (for the on-screen display)
    send_to_log(7," - ".$url);

    if (is_hardware_player())
      echo  'YouTube Video|0|0|'.$url."|\n";
    else
      echo  $url.newline();

    $item_count++;
  }

  // If this is a PC browser then we need to output some headers
  if ( is_pc() )
  {
    header('Content-Disposition: attachment; filename=Playlist.m3u');
    header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
    header('Content-Type: audio/x-mpegurl');
    header("Content-Length: ".ob_get_length());
    ob_flush();
  }

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>