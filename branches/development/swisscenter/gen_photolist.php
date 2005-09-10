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

  // The device can only cope with playlists of a certain size (which should be enough for
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
  
  $delay      = (count($data) > 1 ? 5 : 3600);  // If a single photo, then display for 1 hour.

  $effect = 8; 
  //  1 = Wipe Down                      2 = Wipe Up     
  //  3 = Wipde up/down from center      4 = Wipe up/down to center
  //  5 = Wipe left/up and right/down    6 = Wipe left/down and right/up
  //  7 = Interleave up/down             8 = Fade In
  //  9 = Random Effect from above 
  
  if (is_showcenter())
  {
    // Generate a playlist for the showcenter
  
    foreach ($data as $row)
    {
      if ($item_count >= MAX_PLAYLIST_SIZE )
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
  }
  else 
  {
    // do some javascript here to display a slideshow on the PC
    echo '<script language="javascript" src="slideshow.js"></script>
          <img id="piccy" src="/images/dot.gif">        
          <script language="javascript">
          var slides = new Array('.count($data).');'.newline();

    $i=0;
    foreach ($data as $row)
      echo 'slides['.$i++.'] = "'.$thumb_url.rawurlencode($row['DIRNAME'].$row['FILENAME']).'";'.newline();

    echo 'Slideshow(5, document.getElementById("piccy"), slides, true);
          </script>';
  }

  /**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
