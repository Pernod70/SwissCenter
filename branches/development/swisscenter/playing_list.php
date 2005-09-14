<?
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor
 *************************************************************************************************/

  require_once("base/page.php");
  require_once("base/mysql.php");
  require_once("base/playlist.php");

  $server     = server_address();
  $shuffle    = ($_REQUEST["shuffle"] == "on" ? true : false);
  $type       = un_magic_quote($_REQUEST["type"]);
  $spec       = $_SESSION["play_now"]["spec"]
  $seed       = $_REQUEST["seed"];
  $data       = pl_tracklist($type, $spec, $shuffle, $seed);
  $item_count = 0;

  foreach ($data as $row)
  {
    // Each device  has a maximum playlist size 
    if ($item_count++ >= MAX_PLAYLIST_SIZE )
      break;
      
    echo "3600|8| |".$server."playing_image.php?userid=".$_REQUEST["userid"]."&music_id=".$row["FILE_ID"]."|\n";
  }
  
/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
