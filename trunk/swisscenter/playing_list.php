<?
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor
 *************************************************************************************************/

  require_once( realpath(dirname(__FILE__).'/base/page.php'));
  require_once( realpath(dirname(__FILE__).'/base/capabilities.php'));
  require_once( realpath(dirname(__FILE__).'/base/mysql.php'));
  require_once( realpath(dirname(__FILE__).'/base/playlist.php'));

  $server     = server_address();
  $transition = now_playing_transition();
  $url        = $server."playing_image.php?".current_session();
  $data       = get_tracklist();                            
  $max_size   = max_playlist_size();

  if (support_now_playing())
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
        
      send_to_log(7,' - '.$url."&idx$idx=&type=.jpg");
      echo "$timeout|$transition| |$url&idx=$idx&type=.jpg|\n";    
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
    
    $timeout    = 5;    
    send_to_log(7,"No Support for sync'd music and photos.");
    
    echo "$timeout|$transition| |$url&type=.jpg|\n";    
    echo "$timeout|$transition| |$url&type=.jpg|\n";    
  }
  
/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
