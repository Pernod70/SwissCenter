<?
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor
 *************************************************************************************************/

  require_once( realpath(dirname(__FILE__).'/base/page.php'));
  require_once( realpath(dirname(__FILE__).'/base/mysql.php'));
  require_once( realpath(dirname(__FILE__).'/base/playlist.php'));

  // We output two identical playlist items that both point to the same script (playing_image.php),
  // the only difference is that players that support a "Now Playing" screen will sync the slide
  // change with the end of the music. For other players, we must constantly switch between
  // images (on a 5 second delay).
  
  $server     = server_address();
  $transition = now_playing_transition();
  $timeout    = 5;
  $url        = $server."playing_image.php?".current_session()."&type=.jpg";
  
  send_to_log(7,'Generating List of "Now Playing" images');  
  send_to_log(7,' -'.$url);
  
  echo "$timeout|$transition| |$url|\n";    
  echo "$timeout|$transition| |$url|\n";    
  
/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
