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
  $item_count = 0;
  $media_type = $_REQUEST["media_type"];  
  
  debug_to_log('Generating list of media files to send to the networked media player.');
  
  foreach ($data as $row)
  {
    if ($item_count >= max_playlist_size() )
      break;
      
    if ( is_null($row["TITLE"]) )
      $title = rtrim(file_noext(basename($row["FILENAME"])));
    else
      $title = rtrim($row["TITLE"]);

    // Uses the "stream.php" file to record the fact the file has been requested and then redirect to the actual file
    //
    // NOTE: An extra (unused) parameter is appended onto the end URL to inform the media player of the filetype. 
    //       If this is missing, then the player reports "unknown format" 
    
    $url = $server.'stream.php?media_type='.$media_type.'&file_id='.$row["FILE_ID"].'&ext=.'.file_ext($row["FILENAME"]);
    debug_to_log(' - '.$url);
      
    if (is_hardware_player())
      echo  $title.'|0|0|'.$url."|\n";
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
