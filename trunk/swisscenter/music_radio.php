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

  $menu       = new menu();

  if ( lastfm_enabled() )
    $menu->add_item(str('LASTFM'), '/music_radio_lastfm.php' );
  
  $menu->add_item('ShoutCast', url_add_param('music_radio_shoutcast.php', 'class','shoutcast') );
  $menu->add_item('Live-Radio', url_add_param('music_radio_shoutcast.php', 'class','liveradio') );  
  $menu->add_item(str('BOOKMARKS'), './music_radio_urls.php');

  // Display the page
  page_header(str('LISTEN_RADIO'));
  echo '<center>'.str('SELECT_OPTION').'</center><p>';
  $menu->display();
  page_footer('./index.php');

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
