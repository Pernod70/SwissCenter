<?
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor
 *************************************************************************************************/

  require_once( realpath(dirname(__FILE__).'/base/page.php'));
  require_once( realpath(dirname(__FILE__).'/base/server.php'));
  require_once( realpath(dirname(__FILE__).'/base/utils.php'));
  require_once( realpath(dirname(__FILE__).'/base/playlist.php'));
  require_once( realpath(dirname(__FILE__).'/base/capabilities.php'));
  require_once( realpath(dirname(__FILE__).'/base/file.php'));

//*************************************************************************************************
// Main logic
//*************************************************************************************************

  // The showcenter can only cope with playlists of a certain size (which should be enough for
  // anyone to sit and listen to!). However, the user shouldn't notice as shuffle is done 
  // before the truncate of the playlist (if the user has selected shuffle).

  $server     = server_address();
  $data       = get_tracklist_to_play();
  $max_size   = max_playlist_size();
  $resume     = (isset($_REQUEST["resume"]) && $_REQUEST["resume"] == 'Y');
  $item_count = 0;
  $media_type = 0;
  $file_id    = 0; 
  
  send_to_log(7,'Generating list of media files to send to the networked media player.');
  
  foreach ($data as $row)
  {
    if ($item_count >= $max_size)
      break;

    // We need to identify the media_type. This may have been passed in on the query string (if the list of files
    // are all of the same type) or we may need to determine it from the database.
    if (isset($_REQUEST["media_type"]) && !empty($_REQUEST["media_type"]))
    {
      $media_type = $_REQUEST["media_type"];  
      $file_id    = $row["FILE_ID"];
    }
    else
    {
      find_media_in_db($row["DIRNAME"].$row["FILENAME"], $media_type, $file_id);
    }
      
    if ( is_null($row["TITLE"]) )
      $title = rtrim(file_noext(basename($row["FILENAME"])));
    else
      $title = rtrim($row["TITLE"]);

    // NOTE: An extra (unused) parameter is appended onto the end URL to inform the media player of the filetype. 
    //       If this is missing, then the player reports "unknown format" 
    
    $url = $server.'stream.php?media_type='.$media_type.'&file_id='.$file_id.'&ext=.'.file_ext($row["FILENAME"]);
    send_to_log(7,"- ".$url);
    
    // If this is a hardware player, then we might wish to resume playback of a file partway through
    $start_pos = 0;
    if ( $resume && support_resume() )
    {
      $filename = ucfirst($row["DIRNAME"]).$row["FILENAME"];
      $bookmark_filename = SC_LOCATION."config/bookmarks/".md5("/".$filename).".dat";
      if (file_exists($bookmark_filename))
      {
        $start_pos = (int)trim(file_get_contents($bookmark_filename));
        send_to_log(7,"- Resuming playback from ".$start_pos."%");
      }
    }
      
    if (is_hardware_player())
      echo  $title.'|'.$start_pos.'|0|'.$url."|\n";
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
