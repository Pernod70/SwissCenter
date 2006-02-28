<?
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor
 *************************************************************************************************/

  require_once( realpath(dirname(__FILE__).'/base/page.php'));
  require_once( realpath(dirname(__FILE__).'/base/mysql.php'));
  require_once( realpath(dirname(__FILE__).'/base/utils.php'));
  require_once( realpath(dirname(__FILE__).'/base/file.php'));
  require_once( realpath(dirname(__FILE__).'/base/playlist.php'));

//*************************************************************************************************
// Main logic
//*************************************************************************************************

  // The device can only cope with playlists of a certain size (However, the user 
  // shouldn't notice as shuffle is done before the truncate of the playlist 
  // (if the user has selected shuffle).

  $server     = server_address();
  $data       = get_tracklist_to_play();
  $item_count = 0;
  $delay      = (count($data) > 1 ? get_user_pref('PHOTO_PLAY_TIME','5') : 3600); 
  
  debug_to_log('Generating list of pictures to send to the networked media player.');

  if (is_hardware_player())
  {
    // Generate a playlist for the showcenter
  
    foreach ($data as $row)
    {
      if ($item_count >= max_playlist_size() )
        break;
        
      if ( is_null($row["TITLE"]) )
        $title = rtrim(file_noext(basename($row["FILENAME"])));
      else
        $title = rtrim($row["TITLE"]);
        
      $url = $server.'stream.php?media_type=2&file_id='.$row["FILE_ID"].'&ext=.jpg';
      debug_to_log(' - '.$url);
  
      if (is_hardware_player())
        echo  "$delay|$effect|$title|$url|\n";
      else
        echo  $url.newline();
  
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
      echo 'slides['.$i++.'] = "'.$server.'stream.php?media_type=2&file_id='.$row["FILE_ID"].'&ext=.jpg'.'";'.newline();

    echo 'Slideshow('.$delay.', document.getElementById("piccy"), slides, true);
          </script>';
  }

  /**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
