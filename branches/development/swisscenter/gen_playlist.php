<?
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor
 *************************************************************************************************/

  include_once("base/page.php");
  include_once("base/server.php");
  require_once("base/utils.php");
  require_once("base/playlist.php");
  require_once("base/capabilities.php");
  require_once("base/file.php");

//*************************************************************************************************
// Main logic
//*************************************************************************************************

  // The showcenter can only cope with playlists of a certain size (which should be enough for
  // anyone to sit and listen to!). However, the user shouldn't notice as shuffle is done 
  // before the truncate of the playlist (if the user has selected shuffle).

  $server     = server_address();
  $data       = get_tracklist_to_play();
  $item_count = 0;
  
  foreach ($data as $row)
  {
    if ($item_count >= MAX_PLAYLIST_SIZE )
      break;
      
    if ( is_null($row["TITLE"]) )
      $title = rtrim(file_noext(basename($row["FILENAME"])));
    else
      $title = rtrim($row["TITLE"]);

    if (is_showcenter())
      echo  $title.'|0|0|'.$server.make_url_path(ucfirst($row["DIRNAME"]).$row["FILENAME"])."|\n";
    else
      echo  $server.make_url_path(ucfirst($row["DIRNAME"]).$row["FILENAME"]).newline();

    $item_count++;
  }

  // If this is a non-showcenter browser then we need to output some headers
  
  if (!is_showcenter() )
  {
    // header('Content-Disposition: attachment; filename=Playlist.m3u');
    header("Cache-Control: must-revalidate, post-check=0, pre-check=0"); 
    header('Content-Type: audio/x-mpegurl');
    header("Content-Length: ".ob_get_length());
    ob_flush();
  }

  /**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
