<?php
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor
 *************************************************************************************************/

  require_once( realpath(dirname(__FILE__).'/base/page.php'));
  require_once( realpath(dirname(__FILE__).'/base/categories.php'));
  require_once( realpath(dirname(__FILE__).'/base/rating.php'));
  require_once( realpath(dirname(__FILE__).'/base/playlist.php'));
  require_once( realpath(dirname(__FILE__).'/base/search.php'));
  require_once( realpath(dirname(__FILE__).'/base/filter.php'));

  function display_music_menu($cat_id)
  {
    if(empty($cat_id))
      search_hist_init( 'music.php', get_rating_filter().filter_get_predicate() );
    else
      search_hist_init( 'music.php?cat='.$cat_id, category_select_sql($cat_id, 1).get_rating_filter().filter_get_predicate() );

    if ($cat_id <= 0)
      $prev_page = "music.php?subcat=".abs($cat_id);
    else
      $prev_page = "music.php?subcat=".db_value("select parent_id from categories where cat_id=$cat_id");  
    
    echo '<p>';

    $menu = new menu();
    if (get_sys_pref('browse_music_artist_enabled','YES') == 'YES')
      $menu->add_item( str('BROWSE_ARTIST') ,"music_search.php?sort=artist",true);
    if (get_sys_pref('browse_music_album_artist_enabled','YES') == 'YES')
      $menu->add_item( str('BROWSE_ALBUM_ARTIST') ,"music_search.php?sort=band",true);
    if (get_sys_pref('browse_music_album_enabled','YES') == 'YES') 
      $menu->add_item( str('BROWSE_ALBUM') ,"music_search.php?sort=album",true);
    if (get_sys_pref('browse_music_track_enabled','YES') == 'YES') 
      $menu->add_item( str('BROWSE_TRACK') ,"music_search.php?sort=title",true);
    if (get_sys_pref('browse_music_genre_enabled','YES') == 'YES') 
      $menu->add_item( str('BROWSE_GENRE') ,"music_search.php?sort=genre",true);
    if (get_sys_pref('browse_music_year_enabled','YES') == 'YES') 
      $menu->add_item( str('BROWSE_YEAR') ,"music_search.php?sort=year",true);
    if (get_sys_pref('browse_music_filesystem_enabled','YES') == 'YES') 
      $menu->add_item( str('BROWSE_FILESYSTEM') ,"music_browse.php",true);
    
    if ($menu->num_items() == 1)
    {
      search_hist_init( $prev_page, category_select_sql($cat_id, 1).get_rating_filter().filter_get_predicate() );
      header('Location: '.server_address().$menu->item_url(0));
    } 
    else
    {
      $menu->display(1, style_value("MENU_MUSIC_WIDTH"), style_value("MENU_MUSIC_ALIGN"));
    }
    
    $buttons = array();
    $buttons[] = array('text' => str('QUICK_PLAY'),'url'  => quick_play_link(MEDIA_TYPE_MUSIC,$_SESSION["history"][0]["sql"]));
    $buttons[] = array('text' => filter_text(),'url'  => 'get_filter.php?return='.urlencode('music.php?cat='.$cat_id));

    // Make sure the "back" button goes to the correct page:
    if (category_count(MEDIA_TYPE_MUSIC)==1)
      page_footer('index.php', $buttons);
    else
      page_footer($prev_page, $buttons );
  }

 /**************************************************************************************************
   Main page output
   *************************************************************************************************/

  page_header( str('LISTEN_MUSIC'), '','',1,false,'',MEDIA_TYPE_MUSIC);
  
  if( category_count(MEDIA_TYPE_MUSIC)==1 || isset($_REQUEST["cat"]) )
    display_music_menu($_REQUEST["cat"]);
  elseif ( isset($_REQUEST["subcat"]) )
    display_categories('music.php', 1, $_REQUEST["subcat"]);
  else
    display_categories('music.php', 1);

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
