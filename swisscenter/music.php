<?
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor
 *************************************************************************************************/

  require_once( realpath(dirname(__FILE__).'/base/page.php'));
  require_once( realpath(dirname(__FILE__).'/base/categories.php'));
  require_once( realpath(dirname(__FILE__).'/base/rating.php'));
  require_once( realpath(dirname(__FILE__).'/base/playlist.php'));
  require_once( realpath(dirname(__FILE__).'/base/search.php'));

  function display_music_menu($cat_id)
  {
    if(empty($cat_id))
      search_hist_init( 'music.php', get_rating_filter() );
    else
      search_hist_init( 'music.php?cat='.$cat_id, category_select_sql($cat_id, 1).get_rating_filter() );

    // Prompt the user to select an item
    echo '<center>'.str('SELECT_OPTION').'</center><p>';

    $menu = new menu();
    if (get_sys_pref('browse_music_artist_enabled','YES') == 'YES')
      $menu->add_item( str('BROWSE_ARTIST') ,"music_search.php?sort=artist",true);
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
    
    page_footer('music.php', array(array('text' => str('QUICK_PLAY')
                                        ,'url'  => quick_play_link(MEDIA_TYPE_MUSIC,$_SESSION["history"][0]["sql"]))));
  }

 /**************************************************************************************************
   Main page output
   *************************************************************************************************/

  page_header( str('LISTEN_MUSIC'), '');
  
  if( isset($_REQUEST["cat"]) && !empty($_REQUEST["cat"]) )
    display_music_menu($_REQUEST["cat"]);
  else
    display_categories('music.php', 1);

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
