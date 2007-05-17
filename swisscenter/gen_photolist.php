<?
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor
 *************************************************************************************************/

  require_once( realpath(dirname(__FILE__).'/base/page.php'));
  require_once( realpath(dirname(__FILE__).'/base/mysql.php'));
  require_once( realpath(dirname(__FILE__).'/base/utils.php'));
  require_once( realpath(dirname(__FILE__).'/base/file.php'));
  require_once( realpath(dirname(__FILE__).'/base/playlist.php'));

/**************************************************************************************************
// Notes
//*************************************************************************************************

 [1] The hardware players can only cope with a playlists containing a limited number of entries. 
     This probably varies between players, and so is set in the capabilities.php file and returned
     using the function max_playlist_size().

 [2] The hardware players expect to find an extension on the end of the URL. They use this to determine
     the data format (to play) and also substitute "avi" for a subtitle extension before requesting a
     subtitle.

*/

  // Generate the playlist based on the values passed as part of the request
  generate_tracklist( $_REQUEST["seed"], ($_SESSION["shuffle"] == "on"), $_REQUEST["spec_type"], $_REQUEST["spec"], $_REQUEST["media_type"]);

  $data       = get_tracklist();                            
  $server     = server_address();
  $item_count = 0;
  $effect     = 8;
  $delay      = (count($data) > 1 ? get_user_pref('PHOTO_PLAY_TIME','5') : 3600); 
  
  send_to_log(7,'Generating list of pictures to send to the networked media player.');

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
        
      $url = $server.'stream.php?'.current_session().'&media_type=2&idx='.$item_count.'&ext=.jpg';
      send_to_log(7,' - '.$url);
  
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
    {
      echo 'slides['.$i.'] = "'.$server.'stream.php?media_type=2&idx='.$i.'&ext=.jpg'.'";'.newline();
      $i++;
    }

    echo 'Slideshow('.$delay.', document.getElementById("piccy"), slides, true);
          </script>';
  }

  /**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
