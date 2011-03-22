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

    $browse = array();
    if (get_sys_pref('browse_music_artist_enabled','YES') == 'YES')
      $browse[] = array('text'=>str('BROWSE_ARTIST'), 'url'=>"music_search.php?sort=artist");
    if (get_sys_pref('browse_music_album_artist_enabled','YES') == 'YES')
      $browse[] = array('text'=>str('BROWSE_ALBUM_ARTIST'), 'url'=>"music_search.php?sort=band");
    if (get_sys_pref('browse_music_composer_enabled','YES') == 'YES')
      $browse[] = array('text'=>str('BROWSE_COMPOSER'), 'url'=>"music_search.php?sort=composer");
    if (get_sys_pref('browse_music_album_enabled','YES') == 'YES')
      $browse[] = array('text'=>str('BROWSE_ALBUM'), 'url'=>"music_search.php?sort=album");
    if (get_sys_pref('browse_music_track_enabled','YES') == 'YES')
      $browse[] = array('text'=>str('BROWSE_TRACK'), 'url'=>"music_search.php?sort=title");
    if (get_sys_pref('browse_music_genre_enabled','YES') == 'YES')
      $browse[] = array('text'=>str('BROWSE_GENRE'), 'url'=>"music_search.php?sort=genre");
    if (get_sys_pref('browse_music_year_enabled','YES') == 'YES')
      $browse[] = array('text'=>str('BROWSE_YEAR'), 'url'=>"music_search.php?sort=year");
    if (get_sys_pref('browse_music_discovered_enabled','YES') == 'YES')
      $browse[] = array('text'=>str('BROWSE_DISCOVERED'), 'url'=>"music_search.php?sort=discovered");
    if (get_sys_pref('browse_music_timestamp_enabled','YES') == 'YES')
      $browse[] = array('text'=>str('BROWSE_TIMESTAMP'), 'url'=>"music_search.php?sort=timestamp");
    if (get_sys_pref('browse_music_filesystem_enabled','YES') == 'YES')
      $browse[] = array('text'=>str('BROWSE_FILESYSTEM'), 'url'=>"music_browse.php");

    if (count($browse) == 1)
    {
      search_hist_init( $prev_page, category_select_sql($cat_id, 1).get_rating_filter().filter_get_predicate() );
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

      $menu->display(1, style_value("MENU_MUSIC_WIDTH"), style_value("MENU_MUSIC_ALIGN"));
    }

    $buttons = array();
    $buttons[] = array('text' => str('QUICK_PLAY'),'url'  => quick_play_link(MEDIA_TYPE_MUSIC,$_SESSION["history"][0]["sql"]));
    if (get_sys_pref('NOW_PLAYING_STYLE','ORIGINAL') == 'ORIGINAL')
      $buttons[] = array('text'=>str('NOW_PLAYING_STYLE').': '.str('ENHANCED'), 'url'=> url_set_param(current_url(),'playing','ENHANCED') );
    else
      $buttons[] = array('text'=>str('NOW_PLAYING_STYLE').': '.str('ORIGINAL'), 'url'=> url_set_param(current_url(),'playing','ORIGINAL') );
    $buttons[] = array('text' => filter_text(),'url'  => 'get_filter.php?return='.urlencode('music.php?cat='.$cat_id));

    // Make sure the "back" button goes to the correct page:
    if (category_count(MEDIA_TYPE_MUSIC)==1)
      page_footer('index.php', $buttons);
    else
      page_footer($prev_page, $buttons );
  }

 /**************************************************************************************************
   Main page output
  **************************************************************************************************/

  // Toggle Now Playing screen
  if (isset($_REQUEST["playing"]))
    set_sys_pref('NOW_PLAYING_STYLE',$_REQUEST["playing"]);

  $subtitle = isset($_REQUEST["cat"]) ? db_value('select cat_name from categories where cat_id='.$_REQUEST["cat"]) : '';
  page_header( str('LISTEN_MUSIC'), $subtitle,'',1,false,'',MEDIA_TYPE_MUSIC);

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
