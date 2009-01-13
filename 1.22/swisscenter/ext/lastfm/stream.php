<?php
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor
 *************************************************************************************************/

  require_once( realpath(dirname(__FILE__).'/../../base/page.php'));
  require_once( realpath(dirname(__FILE__).'/../../base/image.php'));
  require_once( realpath(dirname(__FILE__).'/../../base/image_screens.php'));
  require_once( realpath(dirname(__FILE__).'/lastfm.php'));
 
  if (isset($_REQUEST["image_list"]))
  {
    // Clear the Now Playing details
    unset($_SESSION["now_playing"]);

    // List of images to display to the user (changes every 10 seconds)
    $server     = server_address();
    $transition = now_playing_transition();
    $url        = $server."ext/lastfm/stream.php?".current_session()."&now_playing&x=.jpg";
    echo "10|$transition| |$url|\n";
    echo "10|$transition| |$url|\n";
    
  }
  elseif (isset($_REQUEST["generate_pls"]))
  {
    unset($_SESSION["lastfm"]);
    // Login to LastFM and set station
    $lastfm = new lastfm();
    $lastfm->login( get_user_pref('LASTFM_USERNAME'), get_user_pref('LASTFM_PASSWORD') );
    $lastfm->tune_to_station( $_REQUEST["station"] );
    
    // The playlist (pls) that causes the showcenter to connect to our proxy script
    header('Content-Type: audio/x-scpls');
    header('Content-Disposition: attachment; filename="Lastfm.pls"');
    echo "[playlist]\n";
    echo "numberofentries=2\n";
    echo "File1=".$lastfm->stream_url."\n";  
    echo "Title1=LastFM Radio\n";
    echo "Length1=-1\n";
    echo "File2=".$lastfm->stream_url."\n";  
    echo "Title2=LastFM Radio\n";
    echo "Length2=-1\n";
    echo "Version=2\n";
    
    // The playlist that causes the showcenter to connect to our proxy script
//    $url = $lastfm->stream_url;
//    send_to_log(7,'Generating list of media files to send to the networked media player.');
//    send_to_log(7," - ".$url);
//    echo "LastFM Radio Station|0|0|$url|\n";
//    echo "LastFM Radio Station|0|0|$url|\n";

  }
  elseif (isset($_REQUEST["now_playing"]))
  {
    
    // Contacts lastfm and then displays a "Now Playing" screen.
    $lastfm = new lastfm();
    $lastfm->login( get_user_pref('LASTFM_USERNAME'),get_user_pref('LASTFM_PASSWORD'));
    $info = $lastfm->now_playing();

    // Generate and display the "Now Playing" screen.
    // - If EVA700 then only send a new image if the details have changed. Avoids continuous refreshing.
    if (get_player_type()!=='NETGEAR' || $_SESSION["now_playing"]!==$info)
    {
      // Get artist picture list
      if (get_user_pref('LASTFM_IMAGES','YES') == 'YES')
      {
        $photos = $lastfm->artist_images($info["artist"]);
        send_to_log(6,'Artist photos',$photos);
      }

      $_SESSION["now_playing"] = $info;   
      $image = now_playing_image( array( "LENGTH"=>$info["trackduration"]
                                       , "ALBUMART"=>$info["albumcover_large"]
                                       , "TITLE"=>$info["track"]
                                       , "ARTIST"=>$info["artist"]
                                       , "ALBUM"=>$info["album"]
                                       )
                                , '', '', '', $photos );

      // Output the image to the browser
      $image->output('jpeg');
    }
    
  }
  
/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
