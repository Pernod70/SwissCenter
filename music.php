<?php
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor
 *************************************************************************************************/

  require_once( realpath(dirname(__FILE__).'/base/page.php'));
  require_once( realpath(dirname(__FILE__).'/base/categories.php'));
  require_once( realpath(dirname(__FILE__).'/base/rating.php'));
  require_once( realpath(dirname(__FILE__).'/base/playlist.php'));
  require_once( realpath(dirname(__FILE__).'/base/filter.php'));

  function display_music_menu($cat_id)
  {
    if (empty($cat_id))
      page_hist_current_update( current_url(), get_rating_filter().filter_get_predicate() );
    else
      page_hist_current_update( current_url(), category_select_sql($cat_id, MEDIA_TYPE_AUDIO).get_rating_filter().filter_get_predicate() );

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
    if (get_sys_pref('browse_music_mood_enabled','YES') == 'YES')
      $browse[] = array('text'=>str('BROWSE_MOOD'), 'url'=>"music_search.php?sort=mood");
    if (get_sys_pref('browse_music_year_enabled','YES') == 'YES')
      $browse[] = array('text'=>str('BROWSE_YEAR'), 'url'=>"music_search.php?sort=year");
    if (get_sys_pref('browse_music_discovered_enabled','YES') == 'YES')
      $browse[] = array('text'=>str('BROWSE_DISCOVERED'), 'url'=>"music_search.php?sort=discovered");
    if (get_sys_pref('browse_music_timestamp_enabled','YES') == 'YES')
      $browse[] = array('text'=>str('BROWSE_TIMESTAMP'), 'url'=>"music_search.php?sort=timestamp");
    if (get_sys_pref('browse_music_filesystem_enabled','YES') == 'YES')
      $browse[] = array('text'=>str('BROWSE_FILESYSTEM'), 'url'=>"music_browse.php?DIR=");

    if (count($browse) == 1)
    {
      header('Location: '.server_address().$browse[0]["url"].'&hist='.PAGE_HISTORY_REPLACE);
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
    $buttons[] = array('text' => str('QUICK_PLAY'),'url'  => quick_play_link(MEDIA_TYPE_AUDIO, page_hist_current('sql')));
    if (get_sys_pref('NOW_PLAYING_STYLE','ORIGINAL') == 'ORIGINAL')
      $buttons[] = array('text' => str('NOW_PLAYING_STYLE').': '.str('ENHANCED'), 'url' => url_set_params(current_url(), array('playing'=>'ENHANCED', 'hist'=>PAGE_HISTORY_REPLACE)) );
    else
      $buttons[] = array('text' => str('NOW_PLAYING_STYLE').': '.str('ORIGINAL'), 'url' => url_set_params(current_url(), array('playing'=>'ORIGINAL', 'hist'=>PAGE_HISTORY_REPLACE)) );
    $buttons[] = array('text' => filter_text(),'url' => 'get_filter.php');

    // Make sure the "back" button goes to the correct page:
    page_footer(page_hist_previous(), $buttons );
  }

 /**************************************************************************************************
   Main page output
  **************************************************************************************************/

  // Toggle Now Playing screen
  if (isset($_REQUEST["playing"]))
    set_sys_pref('NOW_PLAYING_STYLE',$_REQUEST["playing"]);

  $subtitle = isset($_REQUEST["cat"]) ? db_value('select cat_name from categories where cat_id='.$_REQUEST["cat"]) : '';
  page_header( str('LISTEN_MUSIC'), $subtitle,'',1,false,'',MEDIA_TYPE_AUDIO);

  if( category_count(MEDIA_TYPE_AUDIO)==1 || isset($_REQUEST["cat"]) )
    display_music_menu($_REQUEST["cat"]);
  elseif ( isset($_REQUEST["subcat"]) )
    display_categories('music.php', MEDIA_TYPE_AUDIO, $_REQUEST["subcat"], page_hist_previous());
  else
    display_categories('music.php', MEDIA_TYPE_AUDIO, 0, page_hist_previous());

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
