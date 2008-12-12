<?php
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor
 *************************************************************************************************/

  require_once( realpath(dirname(__FILE__).'/base/page.php'));
  require_once( realpath(dirname(__FILE__).'/base/mysql.php'));
  require_once( realpath(dirname(__FILE__).'/base/utils.php'));
  require_once( realpath(dirname(__FILE__).'/base/file.php'));
  require_once( realpath(dirname(__FILE__).'/base/playlist.php'));

  /**
   * Displays the slideshow on the PC using a simple piece of Javascript
   *
   */
  
  function pc_slideshow()
  {
    $server     = server_address();
    $slides     = count( get_tracklist() );
    $delay      = ($slides > 1 ? get_user_pref('PHOTO_PLAY_TIME','5') : 3600); 
    
    // do some javascript here to display a slideshow on the PC
    echo '<body bgcolor="#000000" TOPMARGIN="0" LEFTMARGIN="0" MARGINHEIGHT="0" MARGINWIDTH="0">'.newline();
    echo '<script language="javascript" src="slideshow.js"></script>'.newline();
    echo '<center><img id="piccy" src="/images/dot.gif"></center>'.newline();
    echo '<script language="javascript">'.newline();
    echo 'var slides = new Array('.count($data).');'.newline();

    for ($i=0; $i < $slides; $i++)
      echo 'slides['.$i.'] = "'.$server.'stream.php?media_type=2&idx='.$i.'&ext=.jpg'.'";'.newline();

    echo 'Slideshow('.$delay.', document.getElementById("piccy"), slides, true);';
    echo '</script>';    
  }
  
  /**
   * Causes the Hardware player to display a slideshow of images.
   * 
   * Notes
   * -----
   * 
   * [1] The hardware players can only cope with a playlists containing a limited number of entries. 
   *     This probably varies between players, and so is set in the capabilities.php file and returned
   *     using the function max_playlist_size().
   * 
   * [2] The hardware players expect to find an extension on the end of the URL. They use this to determine
   *     the data format (to play) and also substitute "avi" for a subtitle extension before requesting a
   *     subtitle.
   */
  
  function player_slideshow()
  {
    $data       = get_tracklist();                            
    $server     = server_address();
    $slides     = min( count($data), max_playlist_size() );
    $delay      = (count($data) > 1 ? get_user_pref('PHOTO_PLAY_TIME','5') : 3600); 
    $effect     = get_sys_pref('PHOTO_TRANSITION_EFFECT',8);

    for ($i=0; $i < $slides; $i++)
    {
      if ( is_null($data[$i]["TITLE"]) )
        $title = rtrim(file_noext(basename($data[$i]["FILENAME"])));
      else
        $title = rtrim($data[$i]["TITLE"]);
        
      $url = $server."stream.php?".current_session()."&media_type=2&idx=$i&ext=.jpg";
      echo  "$delay|$effect|$title|$url|\n";

      send_to_log(7,' - '.$url);  
    }
  }
  
  /**
   * Generate the playlist based on the values passed as part of the request
   */
  
  send_to_log(7,'Generating list of pictures to display as a slideshow');
  generate_tracklist( $_REQUEST["seed"], ($_SESSION["shuffle"] == "on"), $_REQUEST["spec_type"], $_REQUEST["spec"], $_REQUEST["media_type"]);
  
  if (is_hardware_player())
    player_slideshow();
  else 
    pc_slideshow();

  /**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
