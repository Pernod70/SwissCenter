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

  function display_video_menu($cat_id)
  {
    if(empty($cat_id))
      search_hist_init( 'video.php', get_rating_filter().filter_get_predicate() );
    else
      search_hist_init( 'video.php?cat='.$cat_id, category_select_sql($cat_id, 3).get_rating_filter().filter_get_predicate() );

    echo '<center>'.str('SELECT_OPTION').'</center><p>';

    $menu = new menu();
    if (get_sys_pref('browse_video_title_enabled','YES') == 'YES')
      $menu->add_item( str('BROWSE_TITLE')       ,"video_search.php?sort=title",true);
    if (get_sys_pref('browse_video_actor_enabled','YES') == 'YES')
      $menu->add_item( str('BROWSE_ACTOR')       ,"video_search.php?sort=actor",true);
    if (get_sys_pref('browse_video_director_enabled','YES') == 'YES') 
      $menu->add_item( str('BROWSE_DIRECTOR')    ,"video_search.php?sort=director",true);
    if (get_sys_pref('browse_video_genre_enabled','YES') == 'YES') 
      $menu->add_item( str('BROWSE_GENRE')       ,"video_search.php?sort=genre",true);
    if (get_sys_pref('browse_video_year_enabled','YES') == 'YES') 
      $menu->add_item( str('BROWSE_YEAR')        ,"video_search.php?sort=year",true);
    if (get_sys_pref('browse_video_certificate_enabled','YES') == 'YES') 
      $menu->add_item( str('BROWSE_CERTIFICATE') ,"video_search.php?sort=certificate",true);
    if (get_sys_pref('browse_video_filesystem_enabled','YES') == 'YES') 
      $menu->add_item( str('BROWSE_FILESYSTEM')  ,"video_browse.php",true);
      
    if ($menu->num_items() == 1)
    {
      search_hist_init( 'video.php', category_select_sql($cat_id, 3).get_rating_filter() );
      header('Location: '.server_address().$menu->item_url(0));
    } 
    else
    {
      $menu->display();
    }
    
    $buttons = array();
    $buttons[] = array('text' => str('QUICK_PLAY'),'url'  => quick_play_link(MEDIA_TYPE_VIDEO,$_SESSION["history"][0]["sql"]));
    $buttons[] = array('text' => str('FILTER'),'url'  => 'get_filter.php?return='.urlencode('video.php?cat='.$cat_id));

    // Make sure the "back" button goes to the correct page:
    if (category_count(MEDIA_TYPE_VIDEO)==1)
      page_footer('index.php', $buttons);
    else
      page_footer('video.php', $buttons);                                        
  }
  
/**************************************************************************************************
   Main page output
   *************************************************************************************************/

  page_header( str('WATCH_MOVIE') ,'');
  
  if( category_count(MEDIA_TYPE_VIDEO)==1 || !empty($_REQUEST["cat"]) )
    display_video_menu($_REQUEST["cat"]);
  else
    display_categories('video.php', 3);

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
