<?php
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor
 *************************************************************************************************/

  require_once( realpath(dirname(__FILE__).'/base/image_screens.php'));
  require_once( realpath(dirname(__FILE__).'/base/capabilities.php'));

  if ( isset($_REQUEST["list"]))
  {
    // List of images to display to the user (changes every 30 seconds)
    $server     = server_address();
    $url        = $server."music_radio_image.php?".current_session()."&station=".urlencode(un_magic_quote($_REQUEST["station"]))."&x=.jpg";
    $transition = now_playing_transition();
    echo "3600|$transition| |$url|\n";
    echo "3600|$transition| |$url|\n";
  }
  else
  {
    // Generate and display the "Now Playing" screen.    
    $image = station_playing_image(un_magic_quote($_REQUEST["station"]));
    $image->output('jpeg');
  }

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
