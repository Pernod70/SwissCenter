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
      search_hist_init( 'video.php?cat='.$cat_id, category_select_sql($cat_id, MEDIA_TYPE_VIDEO).get_rating_filter().filter_get_predicate() );

    if ($cat_id <= 0)
      $prev_page = "video.php?subcat=".abs($cat_id);
    else
      $prev_page = "video.php?subcat=".db_value("select parent_id from categories where cat_id=$cat_id");

    echo '<p>';

    $browse = array();
    if (get_sys_pref('browse_video_title_enabled','YES') == 'YES')
      $browse[] = array('text'=>str('BROWSE_TITLE'), 'url'=>"video_search.php?sort=title");
    if (get_sys_pref('browse_video_actor_enabled','YES') == 'YES')
      $browse[] = array('text'=>str('BROWSE_ACTOR'), 'url'=>"video_search.php?sort=actor");
    if (get_sys_pref('browse_video_director_enabled','YES') == 'YES')
      $browse[] = array('text'=>str('BROWSE_DIRECTOR'), 'url'=>"video_search.php?sort=director");
    if (get_sys_pref('browse_video_genre_enabled','YES') == 'YES')
      $browse[] = array('text'=>str('BROWSE_GENRE'), 'url'=>"video_search.php?sort=genre");
    if (get_sys_pref('browse_video_year_enabled','YES') == 'YES')
      $browse[] = array('text'=>str('BROWSE_YEAR'), 'url'=>"video_search.php?sort=year");
    if (get_sys_pref('browse_video_certificate_enabled','YES') == 'YES')
      $browse[] = array('text'=>str('BROWSE_CERTIFICATE'), 'url'=>"video_search.php?sort=certificate");
    if (get_sys_pref('browse_video_rating_enabled','YES') == 'YES')
      $browse[] = array('text'=>str('BROWSE_RATING'), 'url'=>"video_search.php?sort=rating");
    if (get_sys_pref('browse_video_discovered_enabled','YES') == 'YES')
      $browse[] = array('text'=>str('BROWSE_DISCOVERED'), 'url'=>"video_search.php?sort=discovered");
    if (get_sys_pref('browse_video_timestamp_enabled','YES') == 'YES')
      $browse[] = array('text'=>str('BROWSE_TIMESTAMP'), 'url'=>"video_search.php?sort=timestamp");
    if (get_sys_pref('browse_video_filesystem_enabled','YES') == 'YES')
      $browse[] = array('text'=>str('BROWSE_FILESYSTEM'), 'url'=>"video_browse.php");

    if (count($browse) == 1)
    {
      search_hist_init( $prev_page, category_select_sql($cat_id, MEDIA_TYPE_VIDEO).get_rating_filter().filter_get_predicate() );
      header('Location: '.server_address().$browse[0]["url"]);
    }
    else
    {
      $page       = (isset($_REQUEST["page"]) ? $_REQUEST["page"] : 1);
      $start      = ($page-1) * MAX_PER_PAGE;
      $end        = min($start+MAX_PER_PAGE,count($browse));
      $last_page  = ceil(count($browse)/MAX_PER_PAGE);

      $menu = new menu();

      if (count($browse) > MAX_PER_PAGE)
      {
        $menu->add_up( url_add_param(current_url(),'page',($page > 1 ? ($page-1) : $last_page)) );
        $menu->add_down( url_add_param(current_url(),'page',($page < $last_page ? ($page+1) : 1)) );
      }

      for ($i=$start; $i<$end; $i++)
        $menu->add_item($browse[$i]["text"], $browse[$i]["url"], true);

      $menu->display(1, style_value("MENU_VIDEO_WIDTH"), style_value("MENU_VIDEO_ALIGN"));
    }

    $buttons = array();
    $buttons[] = array('text' => str('QUICK_PLAY'),'url'  => quick_play_link(MEDIA_TYPE_VIDEO,$_SESSION["history"][0]["sql"]));
    $buttons[] = array('text' => filter_text(),'url'  => 'get_filter.php?return='.urlencode('video.php?cat='.$cat_id));

    // Make sure the "back" button goes to the correct page:
    if (category_count(MEDIA_TYPE_VIDEO)==1)
      page_footer('index.php', $buttons);
    else
      page_footer($prev_page, $buttons );
  }

/**************************************************************************************************
   Main page output
 **************************************************************************************************/

  $subtitle = isset($_REQUEST["cat"]) ? db_value('select cat_name from categories where cat_id='.$_REQUEST["cat"]) : '';
  page_header( str('WATCH_MOVIE'), $subtitle,'',1,false,'',MEDIA_TYPE_VIDEO);

  if( category_count(MEDIA_TYPE_VIDEO)==1 || isset($_REQUEST["cat"]) )
    display_video_menu($_REQUEST["cat"]);
  elseif ( isset($_REQUEST["subcat"]) )
    display_categories('video.php', MEDIA_TYPE_VIDEO, $_REQUEST["subcat"]);
  else
    display_categories('video.php', MEDIA_TYPE_VIDEO);

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
