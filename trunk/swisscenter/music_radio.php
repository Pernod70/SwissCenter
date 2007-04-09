<?
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

  $menu->add_item('ShoutCast','./music_radio_shoutcast.php');
  $menu->add_item('Bookmarks','./music_radio_urls.php');
  $menu->display();
  page_footer('./index.php');

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
