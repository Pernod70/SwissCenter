<?
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor
 *************************************************************************************************/

  require_once( realpath(dirname(__FILE__).'/../../base/page.php'));
  require_once( realpath(dirname(__FILE__).'/../../base/image.php'));
  require_once( realpath(dirname(__FILE__).'/../../base/image_screens.php'));
  require_once( realpath(dirname(__FILE__).'/lastfm.php'));
 
  if (isset($_REQUEST["image_list"]))
  {
    
    // List of images to display to the user (changes every 30 seconds)
    $server     = server_address();
    $transition = now_playing_transition();
    $url        = $server."ext/lastfm/stream.php?".current_session()."&now_playing&x=.jpg";
    echo "30|$transition| |$url|\n";
    echo "30|$transition| |$url|\n";
    
  }
  elseif (isset($_REQUEST["generate_pls"]))
  { 
    
    // The playlist (pls) that causes the showcenter to connect to our proxy script
    header('Content-Type: audio/x-scpls');
    echo "[playlist]\n";
    echo "NumberOfEntries=1\n";
    echo "File1=".server_address()."ext/lastfm/stream.php?".current_session()."&station=".$_REQUEST["station"]."\n";
    echo "Title1=LastFM Radio Station\n";
    echo "Length1=-1\n";
    echo "Version=2\n";
    
  }
  elseif (isset($_REQUEST["now_playing"]))
  {
    
    // Contacts lastfm and then displays a "Now Playing" screen.
    $lastfm = new lastfm();
    $lastfm->login( get_user_pref('LASTFM_USERNAME'),get_user_pref('LASTFM_PASSWORD'));
    $info = $lastfm->now_playing();
    
    // Get artist picture list
    $photos = $lastfm->artist_images($info["artist"]);
    send_to_log(1,'Artist photos',$photos);

    // Generate and display the "Now Playing" screen.    
    $image = now_playing_image( array( "LENGTH"=>$info["trackduration"]
                              , "ALBUMART"=>$info["albumcover_large"]
                              , "TITLE"=>$info["track"]
                              , "ARTIST"=>$info["artist"]
                              , "ALBUM"=>$info["album"]),'','','',$photos );

    // Output the image to the browser
    $image->output('jpeg');
    
  }
  else 
  {
    // Acts as a proxy and streams the lastfm music to the showcenter
    $lastfm  = new lastfm();
    $station = $_REQUEST["station"];
    
    if ($lastfm->login( get_user_pref('LASTFM_USERNAME') , get_user_pref('LASTFM_PASSWORD') ))
    {
      if ($lastfm->tune_to_station( $station) )
        $lastfm->stream( 86400 );
    }
  }
  
/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>