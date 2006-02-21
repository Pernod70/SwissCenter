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

  // The device can only cope with playlists of a certain size (However, the user 
  // shouldn't notice as shuffle is done before the truncate of the playlist 
  // (if the user has selected shuffle).

  $server     = server_address();
  $data       = get_tracklist_to_play();
  $item_count = 0;
  $x          = SCREEN_WIDTH;
  $y          = SCREEN_HEIGHT;
  $thumb_url  = $server.'thumb.php?type=jpg&x='.$x.'&y='.$y.'&src=';  
  $delay      = (count($data) > 1 ? get_user_pref('PHOTO_PLAY_TIME','5') : 3600); 

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
    echo '<body bgcolor="#000000" TOPMARGIN="0" LEFTMARGIN="0" MARGINHEIGHT="0" MARGINWIDTH="0">
          <script language="javascript" src="slideshow.js"></script>
          <center><img id="piccy" src="/images/dot.gif"></center>
          <script language="javascript">
          var slides = new Array('.count($data).');'.newline();

    $i=0;
    foreach ($data as $row)
      echo 'slides['.$i++.'] = "'.$thumb_url.rawurlencode($row['DIRNAME'].$row['FILENAME']).'";'.newline();

    echo 'Slideshow('.$delay.', document.getElementById("piccy"), slides, true);
          </script>';
  }

  /**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
