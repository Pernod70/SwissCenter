<?
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor
 *************************************************************************************************/

  require_once("base/page.php");
  require_once("base/mysql.php");
  require_once("base/playlist.php");

  $server     = server_address();
  $data       = get_tracklist_to_play();
  $item_count = 0;

  foreach ($data as $row)
  {
    // Each device  has a maximum playlist size 
    if ($item_count++ >= MAX_PLAYLIST_SIZE )
      break;
      
    echo "3600|8| |".$server."playing_image.php?".current_session()."&music_id=".$row["FILE_ID"]."|\n";
  }
  
/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
