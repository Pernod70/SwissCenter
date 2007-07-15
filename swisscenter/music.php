<?
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

    // Prompt the user to select an item
    echo '<center>'.str('SELECT_OPTION').'</center><p>';

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
      search_hist_init( 'music.php', category_select_sql($cat_id, 1).get_rating_filter() );
      header('Location: '.server_address().$menu->item_url(0));
    } 
    else
    {
      $menu->display();
    }
    
    $buttons = array();
    $buttons[] = array('text' => str('QUICK_PLAY'),'url'  => quick_play_link(MEDIA_TYPE_MUSIC,$_SESSION["history"][0]["sql"]));
    $buttons[] = array('text' => str('FILTER'),'url'  => 'get_filter.php?return='.urlencode('music.php?cat='.$cat_id));

    // Make sure the "back" button goes to the correct page:
    if (category_count(MEDIA_TYPE_MUSIC)==1)
      page_footer('index.php', $buttons);
    else
      page_footer('music.php', $buttons);                                        
  }

 /**************************************************************************************************
   Main page output
   *************************************************************************************************/

  page_header( str('LISTEN_MUSIC'), '');
  
  if( category_count(MEDIA_TYPE_MUSIC)==1 || !empty($_REQUEST["cat"]) )
    display_music_menu($_REQUEST["cat"]);
  else
    display_categories('music.php', 1);

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
