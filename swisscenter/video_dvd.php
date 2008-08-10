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

  function display_dvd_menu($cat_id)
  {
    if(empty($cat_id))
      search_hist_init( 'video_dvd.php', get_rating_filter().filter_get_predicate() );
    else
      search_hist_init( 'video_dvd.php?cat='.$cat_id, category_select_sql($cat_id, MEDIA_TYPE_DVD).get_rating_filter().filter_get_predicate() );

    if ($cat_id <= 0)
      $prev_page = "video_dvd.php?subcat=".abs($cat_id);
    else
      $prev_page = "video_dvd.php?subcat=".db_value("select parent_id from categories where cat_id=$cat_id");
      
    echo '<p>';

    $menu = new menu();
    if (get_sys_pref('browse_video_title_enabled','YES') == 'YES')
      $menu->add_item( str('BROWSE_TITLE')       ,"video_dvd_search.php?sort=title",true);
    if (get_sys_pref('browse_video_actor_enabled','YES') == 'YES')
      $menu->add_item( str('BROWSE_ACTOR')       ,"video_dvd_search.php?sort=actor",true);
    if (get_sys_pref('browse_video_director_enabled','YES') == 'YES') 
      $menu->add_item( str('BROWSE_DIRECTOR')    ,"video_dvd_search.php?sort=director",true);
    if (get_sys_pref('browse_video_genre_enabled','YES') == 'YES') 
      $menu->add_item( str('BROWSE_GENRE')       ,"video_dvd_search.php?sort=genre",true);
    if (get_sys_pref('browse_video_year_enabled','YES') == 'YES') 
      $menu->add_item( str('BROWSE_YEAR')        ,"video_dvd_search.php?sort=year",true);
    if (get_sys_pref('browse_video_certificate_enabled','YES') == 'YES') 
      $menu->add_item( str('BROWSE_CERTIFICATE') ,"video_dvd_search.php?sort=certificate",true);
    if (get_sys_pref('browse_video_filesystem_enabled','YES') == 'YES') 
      $menu->add_item( str('BROWSE_FILESYSTEM')  ,"video_dvd_browse.php",true);
      
    if ($menu->num_items() == 1)
    {
      search_hist_init( $prev_page, category_select_sql($cat_id, MEDIA_TYPE_DVD).get_rating_filter().filter_get_predicate() );
      header('Location: '.server_address().$menu->item_url(0));
    } 
    else
    {
      $menu->display(1, style_value("MENU_DVD_WIDTH"), style_value("MENU_DVD_ALIGN"));
    }
    
    $buttons = array();
    $buttons[] = array('text' => filter_text(),'url'  => 'get_filter.php?return='.urlencode('video_dvd.php?cat='.$cat_id));

    // Make sure the "back" button goes to the correct page:
    if (category_count(MEDIA_TYPE_DVD)==1)
      page_footer('index.php', $buttons);
    else
      page_footer($prev_page, $buttons );
  }
  
/**************************************************************************************************
   Main page output
   *************************************************************************************************/

  page_header( str('WATCH_DVD') , '','',1,false,'',MEDIA_TYPE_DVD);
  
  if( category_count(MEDIA_TYPE_DVD)==1 || isset($_REQUEST["cat"]) )
    display_dvd_menu($_REQUEST["cat"]);
  elseif ( isset($_REQUEST["subcat"]) )
    display_categories('video_dvd.php?submenu=video', MEDIA_TYPE_DVD, $_REQUEST["subcat"]);
  else 
    display_categories('video_dvd.php?submenu=video', MEDIA_TYPE_DVD);

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
