<?
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor
 *************************************************************************************************/

  require_once( realpath(dirname(__FILE__).'/base/page.php'));
  require_once( realpath(dirname(__FILE__).'/base/mysql.php'));
  require_once( realpath(dirname(__FILE__).'/base/playlist.php'));

  $server     = server_address();
  $data       = get_tracklist_to_play();
  $max_size   = max_playlist_size();
  $item_count = 0;
  $session    = current_session();
  $transition = now_playing_transition();
  
  send_to_log(7,'Generating List of "Now Playing" images');
  
  if ( support_now_playing() )
  {
    // For players that support the "Now Playing" screen we can output a list of images that
    // should be displayed at the same time as the music is playing. These players automatically
    // request the revelant image as the track starts playing.
    foreach ($data as $row)
    {
      // Each device  has a maximum playlist size 
      if ($item_count++ >= $max_size)
        break;
        
      $url = $server."playing_image.php?".$session."&music_id=".$row["FILE_ID"]."&type=.jpg";
      send_to_log(7,$url);
  
      echo "3600|$transition| |".$url."|\n";
    }    
  }
  else 
  {
    // For other players we need to perform a bit of a hack. Basically, we get the player to
    // repeatedly request an image every <n> seconds. The routine that generates the image
    // uses the information from the last track requested.
    set_user_pref('LAST_PLAYED_ID',$data[0]["FILE_ID"]);
    $url = $server."playing_image.php?".$session."&music_id=NONE&type=.jpg";
    send_to_log(7,$url);
    echo "5|$transition| |".$url."|\n";    
    echo "5|$transition| |".$url."|\n";    
  }
  
/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
