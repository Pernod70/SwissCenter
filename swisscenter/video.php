<?
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor
 *************************************************************************************************/

  require_once( realpath(dirname(__FILE__).'/base/page.php'));
  require_once( realpath(dirname(__FILE__).'/base/categories.php'));
  require_once( realpath(dirname(__FILE__).'/base/rating.php'));
  require_once( realpath(dirname(__FILE__).'/base/playlist.php'));
  require_once( realpath(dirname(__FILE__).'/base/search.php'));

  function display_video_menu($cat_id)
  {
    if(empty($cat_id))
      search_hist_init( 'video.php', get_rating_filter() );
    else
      search_hist_init( 'video.php?cat='.$cat_id, category_select_sql($cat_id, 3).get_rating_filter() );

    echo '<center>'.str('SELECT_OPTION').'</center><p>';

    $menu = new menu();
    $menu->add_item( str('BROWSE_TITLE')       ,"video_search.php?sort=title",true);
    $menu->add_item( str('BROWSE_ACTOR')       ,"video_search.php?sort=actor",true);
    $menu->add_item( str('BROWSE_DIRECTOR')    ,"video_search.php?sort=director",true);
    $menu->add_item( str('BROWSE_GENRE')       ,"video_search.php?sort=genre",true);
    $menu->add_item( str('BROWSE_YEAR')        ,"video_search.php?sort=year",true);
    $menu->add_item( str('BROWSE_CERTIFICATE') ,"video_search.php?sort=certificate",true);
    $menu->add_item( str('BROWSE_FILESYSTEM')  ,"video_browse.php",true);
    $menu->display();
    
    page_footer('video.php', array(array('text' => str('QUICK_PLAY')
                                        ,'url'  => quick_play_link(MEDIA_TYPE_VIDEO,$_SESSION["history"][0]["sql"]))));
  }
  
/**************************************************************************************************
   Main page output
   *************************************************************************************************/

  page_header( str('WATCH_MOVIE') ,'');
  
  if( isset($_REQUEST["cat"]) && !empty($_REQUEST["cat"]) )
    display_video_menu($_REQUEST["cat"]);
  else
    display_categories('video.php', 3);


/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
