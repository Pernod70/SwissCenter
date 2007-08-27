<?php
/**************************************************************************************************
   SWISScenter Source                                                              Itzchak Rehberg
 *************************************************************************************************/

  require_once( realpath(dirname(__FILE__).'/base/page.php'));
  require_once( realpath(dirname(__FILE__).'/base/playlist.php'));
  require_once( realpath(dirname(__FILE__).'/base/users.php'));
  require_once( realpath(dirname(__FILE__).'/base/file.php'));
  require_once( realpath(dirname(__FILE__).'/messages_db.php'));

  $menu       = new menu();

  page_header(str('LISTEN_RADIO'));
  $menu->add_item('ShoutCast', url_add_param('music_radio_shoutcast.php', 'class','shoutcast') );
  $menu->add_item('Live-Radio',url_add_param('music_radio_shoutcast.php', 'class','liveradio') );
  $menu->add_item(str('BOOKMARKS'),'./music_radio_urls.php');
  $menu->display();
  page_footer('./index.php');

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
