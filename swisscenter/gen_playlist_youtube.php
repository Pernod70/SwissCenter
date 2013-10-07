<?php
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor
 *************************************************************************************************/

  require_once( realpath(dirname(__FILE__).'/base/session.php'));
  require_once( realpath(dirname(__FILE__).'/base/server.php'));
  require_once( realpath(dirname(__FILE__).'/base/utils.php'));
  require_once( realpath(dirname(__FILE__).'/base/capabilities.php'));
  require_once( realpath(dirname(__FILE__).'/base/file.php'));

  // Log details of the playlist request
  send_to_log(0,"Playlist: ".$_SERVER["REQUEST_METHOD"]." ".current_url()." by client (".client_ip().")");

  // Generate the playlist based on the values passed as part of the request
  $seed       = $_REQUEST["seed"];
  $shuffle    = isset($_REQUEST["shuffle"]) ? ($_REQUEST["shuffle"] == 'on') : ($_SESSION["shuffle"] == 'on');
  $video_ids  = explode(',', $_SESSION["play_now"]["spec"]);
  $server     = server_address();
  $max_size   = max_playlist_size();
  $item_count = 0;

  // Shuffle the videos if required
  if ($shuffle && count($video_ids)>1)
    shuffle_fisherYates($video_ids,$seed);

  send_to_log(7,'Generating list of YouTube videos to send to the networked media player.');

  foreach ($video_ids as $id)
  {
    if ($item_count >= $max_size)
      break;

    $url = $server.'stream_url.php?'.current_session().'&youtube_id='.$id.'&ext=.mp4';

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
