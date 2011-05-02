<?php
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor
 *************************************************************************************************/
  require_once( realpath(dirname(__FILE__).'/base/page.php'));
  require_once( realpath(dirname(__FILE__).'/base/menu.php'));
  require_once( realpath(dirname(__FILE__).'/base/mysql.php'));
  require_once( realpath(dirname(__FILE__).'/base/language.php'));
  require_once( realpath(dirname(__FILE__).'/base/search.php'));
  require_once( realpath(dirname(__FILE__).'/base/playlist.php'));

  $back_url = search_hist_most_recent();

  if (isset($_REQUEST["music"]))
  {
    if ($_REQUEST["music"] == 'iradio')
    {
      $playlist_url = un_magic_quote(rawurldecode($_REQUEST["url"]));
      $station_name = un_magic_quote(rawurldecode($_REQUEST["title"]));
      $_SESSION['background_music'] = preg_get('/(gen_playlist_iradio.*ext=\.pls)/U', play_internet_radio(0,$playlist_url,$station_name));
    }
    else
      $_SESSION['background_music'] = '*';
    header('Location: '.$back_url["url"]);
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
    page_footer( $back_url["url"] );
  }

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
