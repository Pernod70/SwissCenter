<?
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor
 *************************************************************************************************/

  require_once("base/page.php");
  require_once("base/mysql.php");
  require_once("base/playlist.php");

  $server     = 'http://'.$_SESSION["opts"]["server_address"].'/';
  $shuffle    = ($_REQUEST["shuffle"] == "on" ? true : false);
  $type       = un_magic_quote($_REQUEST["type"]);
  $spec       = un_magic_quote($_REQUEST["spec"]);
  $seed       = $_REQUEST["seed"];
  $data       = pl_tracklist($type, $spec, $shuffle, $seed);
  $item_count = 0;

  foreach ($data as $row)
  {
    // Showcenter has a maximum playlist size of 4000 entries
    if ($item_count++ >= 4000)
      break;
      
    echo "3600|8| |".$server."playing_image.php?music_id=".$row["FILE_ID"]."|\n";
  }
  
/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
