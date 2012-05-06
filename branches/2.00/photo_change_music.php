<?php
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor
 *************************************************************************************************/
  require_once( realpath(dirname(__FILE__).'/base/page.php'));
  require_once( realpath(dirname(__FILE__).'/base/menu.php'));
  require_once( realpath(dirname(__FILE__).'/base/mysql.php'));
  require_once( realpath(dirname(__FILE__).'/base/language.php'));
  require_once( realpath(dirname(__FILE__).'/base/playlist.php'));

  if (isset($_REQUEST["music"]))
  {
    if ($_REQUEST["music"] == 'iradio')
    {
      $playlist_url = un_magic_quote(rawurldecode($_REQUEST["url"]));
      $station_name = un_magic_quote(rawurldecode($_REQUEST["title"]));
      $_SESSION['background_music'] = preg_get('/(gen_playlist_iradio.*ext=\.pls)/U', play_internet_radio(0, $playlist_url, $station_name));
    }
    else
      $_SESSION['background_music'] = $_REQUEST["music"];
    page_hist_pop();
    header('Location: '.page_hist_previous());
  }
  else
  {
    page_header( str('VIEW_PHOTO'),str('PHOTOS_MUSIC_CHANGE'),'',1,false,'',MEDIA_TYPE_PHOTO );

    echo '<p>';
    $menu = new menu();

    $menu->add_item( str('PHOTOS_MUSIC_NONE'),        'photo_change_music.php?music=');
    $menu->add_item( str('PHOTOS_MUSIC_CURRENT'),     'photo_change_music.php?music='.urlencode('*'));
    $menu->add_item( str('PHOTOS_MUSIC_IRADIO'),      'photo_change_music_radio_urls.php');

    $menu->display(1, style_value("MENU_PHOTO_WIDTH"), style_value("MENU_PHOTO_ALIGN"));
    page_footer( page_hist_previous() );
  }

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
