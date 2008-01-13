<?php
/**************************************************************************************************
   SWISScenter Source                                                              Itzchak Rehberg
 *************************************************************************************************/

  require_once( realpath(dirname(__FILE__).'/base/page.php'));
  require_once( realpath(dirname(__FILE__).'/base/playlist.php'));
  require_once( realpath(dirname(__FILE__).'/base/users.php'));
  require_once( realpath(dirname(__FILE__).'/base/file.php'));
  require_once( realpath(dirname(__FILE__).'/messages_db.php'));
  require_once( realpath(dirname(__FILE__).'/ext/lastfm/lastfm.php'));

  $general_menu  = new menu();
  $personal_menu = new menu();
  
  // General LastFM radio stations
  $general_menu->add_item( str('LISTEN_LASTFM_TAGS')           , '/music_radio_lastfm_tags.php' );
  $general_menu->add_item( str('LISTEN_LASTFM_ARTISTS')        , '/music_radio_lastfm_artists.php' );
  
  // Stations that are generated based on the user's personal profile/listening habits
  $personal_menu->add_item( str('LISTEN_LASTFM_MY_STATION')    , play_lastfm( LASTFM_USER, 'personal') );
  $personal_menu->add_item( str('LISTEN_LASTFM_RECOMMEND')     , play_lastfm( LASTFM_USER, 'recommended/100') );
  $personal_menu->add_item( str('LISTEN_LASTFM_LOVED')         , play_lastfm( LASTFM_USER, 'loved') );
  $personal_menu->add_item( str('LISTEN_LASTFM_NEIGHBOURHOOD') , play_lastfm( LASTFM_USER, 'neighbours') );

  // Display the page
  page_header(str('LISTEN_LASTFM'));
  echo '<center>'.str('LASTFM_PROMPT_GENERAL').'</center>';
  $general_menu->display();
  echo '<center>'.str('LASTFM_PROMPT_PERSONAL').'</center>';
  $personal_menu->display( $general_menu->num_items()+1 );
  page_footer('./music_radio.php');

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
