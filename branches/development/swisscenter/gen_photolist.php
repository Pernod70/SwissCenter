<?
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor
 *************************************************************************************************/

  require_once("base/page.php");
  require_once("base/mysql.php");
  require_once("base/utils.php");
  require_once("base/file.php");
  require_once("base/playlist.php");

//*************************************************************************************************
// Main logic
//*************************************************************************************************

  // The showcenter can only cope with playlists of 4000 items (which should be enough for
  // anyone to sit and listen to!). However, the user shouldn't notice as shuffle is done 
  // before the truncate of the playlist (if the user has selected shuffle).

  $server     = server_address();
  $type       = un_magic_quote($_REQUEST["type"]);
  $spec       = un_magic_quote($_REQUEST["spec"]);
  $seed       = $_REQUEST["seed"];
  $shuffle    = ($_REQUEST["shuffle"] == "on" ? true : false);
  $item_count = 0;
  $data       = pl_tracklist($type, $spec, $shuffle, $seed);
  $x          = 625;
  $y          = ( get_screen_type() == 'PAL' ? 500 : 418);
  $thumb_url  = $server.'thumb.php?x='.$x.'&y='.$y.'&src='; 
  
  $effect = 8; 
  //  1 = Wipe Down                      2 = Wipe Up     
  //  3 = Wipde up/down from center      4 = Wipe up/down to center
  //  5 = Wipe left/up and right/down    6 = Wipe left/down and right/up
  //  7 = Interleave up/down             8 = Fade In
  //  9 = Random Effect from above 

  $delay      = (count($data) > 1 ? 5 : 3600);  // If a single photo, then display for 1 hour.
  
  foreach ($data as $row)
  {
    if ($item_count >= 4000)
      break;
      
    if ( is_null($row["TITLE"]) )
      $title = rtrim(file_noext(basename($row["FILENAME"])));
    else
      $title = rtrim($row["TITLE"]);

    if (is_showcenter())
      echo  "$delay|$effect|$title|".$thumb_url.rawurlencode(ucfirst($row["DIRNAME"]).$row["FILENAME"])."|\n";
    else
      echo  $thumb_url.rawurlencode(ucfirst($row["DIRNAME"]).$row["FILENAME"]).newline();

    $item_count++;
  }
  
  // If this is a non-showcenter browser then we need to output some headers
  
  if (!is_showcenter() )
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
