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

  $menu = new menu();

//  if ( lastfm_enabled() )
//    $menu->add_item(str('LASTFM'), '/music_radio_lastfm.php' );

  $menu->add_item(str('IRADIO_SHOUTCAST'), url_add_param('music_radio_shoutcast.php', 'class','shoutcast'), true );
  $menu->add_item(str('IRADIO_TUNEIN'), url_add_param('music_radio_tunein.php', 'class','tunein'), true );
  $menu->add_item(str('IRADIO_LIVERADIO'), url_add_param('music_radio_shoutcast.php', 'class','liveradio'), true );
  $menu->add_item(str('IRADIO_LIVE365'), url_add_param('music_radio_shoutcast.php', 'class','live365'), true );
  $menu->add_item(str('IRADIO_ICECAST'), url_add_param('music_radio_shoutcast.php', 'class','icecast'), true );
  $menu->add_item(str('IRADIO_STEAMCAST'), url_add_param('music_radio_shoutcast.php', 'class','steamcast'), true );
  $menu->add_item(str('BOOKMARKS'), 'music_radio_urls.php', true);

  // Display the page
  page_header(str('LISTEN_RADIO') , '','',1,false,'',MEDIA_TYPE_RADIO);
  echo '<p>';
  $menu->display(1, style_value("MENU_RADIO_WIDTH"), style_value("MENU_RADIO_ALIGN"));
  page_footer( page_hist_previous() );

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
