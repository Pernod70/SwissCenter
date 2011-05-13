<?php
/**************************************************************************************************
   SWISScenter Source                                                              Nigel Barnes
 *************************************************************************************************/

  require_once( realpath(dirname(__FILE__).'/base/page.php'));
  require_once( realpath(dirname(__FILE__).'/base/users.php'));

  // Display the page
  page_header(str('WATCH_INTERNET_TV'), '','',1,false,'',MEDIA_TYPE_INTERNET_TV);

  $menu = new menu();
//  $menu->add_item( str('SHOUTCAST_TV'), 'shoutcast_tv.php' );

  // Options only available on PCH
  if ( is_pc() || get_player_model() > 400 )
  {
    $menu->add_item( str('YOUTUBE'), 'youtube_menu.php' );
    $menu->add_item( str('APPLE_TRAILERS'), 'apple_trailer.php' );
  }
  $menu->add_item( str('FILM_TRAILERS'), 'film_trailer.php' );
  $menu->add_item( str('TOMA_INTERNET_TV'), 'internet_tv_toma.php' );

  // Adult restricted options
  if (get_current_user_rank() >= 100)
  {
    $menu->add_item( str('YOUPORN'), 'youporn_menu.php' );
    $menu->add_item( str('FTV_GIRLS'), 'ftvgirls.php' );
  }
  $menu->add_item( str('BOOKMARKS'), 'internet_tv_urls.php' );

  $menu->display(1, style_value("MENU_INTERNET_TV_WIDTH"), style_value("MENU_INTERNET_TV_ALIGN"));

  // Make sure the "back" button goes to the correct page:
  page_footer('./index.php?submenu=internet');

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
