<?php
/**************************************************************************************************
   SWISScenter Source                                                              Nigel Barnes
 *************************************************************************************************/

  require_once( realpath(dirname(__FILE__).'/base/page.php'));
  require_once( realpath(dirname(__FILE__).'/base/users.php'));

  $trakt = db_row("select u.user_id, u.name, un.value username, pw.value password
                   from users u join user_prefs un on (un.user_id = u.user_id and un.name = 'TRAKT_USERNAME')
                                join user_prefs pw on (pw.user_id = u.user_id and pw.name = 'TRAKT_PASSWORD')
                   where u.user_id=".get_current_user_id());

  // Display the page
  page_header(str('WATCH_INTERNET_TV'), '','',1,false,'',MEDIA_TYPE_INTERNET_TV);

  $menu = new menu();

  // Options only available on PCH
  if ( is_pc() || get_player_model() > 400 )
  {
    $menu->add_item( str('YOUTUBE'), 'youtube_menu.php' );
    $menu->add_item( str('APPLE_TRAILERS'), 'apple_trailer.php' );
    $menu->add_item( str('VIDEOBASH'), 'videobash_menu.php' );
  }
  if ($trakt)
    $menu->add_item( str('TRAKT'), 'trakt.php' );

  // Adult restricted options
  if (get_current_user_rank() >= 100)
  {
    $menu->add_item( str('YOUPORN'), 'youporn_menu.php' );
    $menu->add_item( str('FTV_GIRLS'), 'ftvgirls.php' );
  }
  $menu->add_item( str('BOOKMARKS'), 'internet_tv_urls.php' );

  $menu->display(1, style_value("MENU_INTERNET_TV_WIDTH"), style_value("MENU_INTERNET_TV_ALIGN"));

  // Make sure the "back" button goes to the correct page:
  page_footer( page_hist_previous() );

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
