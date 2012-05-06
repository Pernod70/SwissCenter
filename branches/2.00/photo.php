<?php
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor
 *************************************************************************************************/

  require_once( realpath(dirname(__FILE__).'/base/page.php'));
  require_once( realpath(dirname(__FILE__).'/base/categories.php'));
  require_once( realpath(dirname(__FILE__).'/base/rating.php'));
  require_once( realpath(dirname(__FILE__).'/base/playlist.php'));
  require_once( realpath(dirname(__FILE__).'/base/filter.php'));

  function display_photo_menu($cat_id)
  {
    if (empty($cat_id))
      page_hist_current_update( current_url(), get_rating_filter().filter_get_predicate() );
    else
      page_hist_current_update( current_url(), category_select_sql($cat_id, 2).get_rating_filter().filter_get_predicate() );

    echo '<p>';

    $browse = array();
    if (get_sys_pref('browse_photo_album_enabled','YES') == 'YES')
      $browse[] = array('text'=>str('BROWSE_PHOTO_ALBUM'), 'url'=>"photo_search.php?sort=title");
    if (get_sys_pref('browse_photo_title_enabled','YES') == 'YES')
      $browse[] = array('text'=>str('BROWSE_PHOTO_TITLE'), 'url'=>"photo_search.php?sort=filename");
    if (get_sys_pref('browse_iptc_byline_enabled','YES') == 'YES')
      $browse[] = array('text'=>str('BROWSE_IPTC_BYLINE'), 'url'=>"photo_search.php?sort=iptc_byline");
//    if (get_sys_pref('browse_iptc_caption_enabled','YES') == 'YES')
//      $browse[] = array('text'=>str('BROWSE_IPTC_CAPTION'), 'url'=>"photo_search.php?sort=iptc_caption");
    if (get_sys_pref('browse_iptc_location_enabled','YES') == 'YES')
      $browse[] = array('text'=>str('BROWSE_IPTC_LOCATION'), 'url'=>"photo_search.php?sort=iptc_location");
    if (get_sys_pref('browse_iptc_city_enabled','YES') == 'YES')
      $browse[] = array('text'=>str('BROWSE_IPTC_CITY'), 'url'=>"photo_search.php?sort=iptc_city");
    if (get_sys_pref('browse_iptc_province_state_enabled','YES') == 'YES')
      $browse[] = array('text'=>str('BROWSE_IPTC_PROVINCE_STATE'), 'url'=>"photo_search.php?sort=iptc_province_state");
    if (get_sys_pref('browse_iptc_country_enabled','YES') == 'YES')
      $browse[] = array('text'=>str('BROWSE_IPTC_COUNTRY'), 'url'=>"photo_search.php?sort=iptc_country");
//    if (get_sys_pref('browse_iptc_keywords_enabled','YES') == 'YES')
//      $browse[] = array('text'=>str('BROWSE_IPTC_KEYWORDS'), 'url'=>"photo_search.php?sort=iptc_keywords");
//    if (get_sys_pref('browse_iptc_suppcategory_enabled','YES') == 'YES')
//      $browse[] = array('text'=>str('BROWSE_IPTC_SUPPCATEGORY'), 'url'=>"photo_search.php?sort=iptc_suppcategory");
    if (get_sys_pref('browse_xmp_rating_enabled','YES') == 'YES')
      $browse[] = array('text'=>str('BROWSE_XMP_RATING'), 'url'=>"photo_search.php?sort=xmp_rating");
    if (get_sys_pref('browse_photo_discovered_enabled','YES') == 'YES')
      $browse[] = array('text'=>str('BROWSE_DISCOVERED'), 'url'=>"photo_search.php?sort=discovered");
    if (get_sys_pref('browse_photo_timestamp_enabled','YES') == 'YES')
      $browse[] = array('text'=>str('BROWSE_TIMESTAMP'), 'url'=>"photo_search.php?sort=timestamp");
    if (get_sys_pref('browse_photo_filesystem_enabled','YES') == 'YES')
      $browse[] = array('text'=>str('BROWSE_FILESYSTEM'), 'url'=>"photo_browse.php?DIR=");

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

      $menu->display(1, style_value("MENU_PHOTO_WIDTH"), style_value("MENU_PHOTO_ALIGN"));
    }

    $buttons = array();
    $buttons[] = array('text' => str('QUICK_PLAY'),'url'  => quick_play_link(MEDIA_TYPE_PHOTO, page_hist_current('sql')));
    $buttons[] = array('text' => filter_text(),'url'  => 'get_filter.php');

    // Make sure the "back" button goes to the correct page:
    page_footer(page_hist_previous(), $buttons );
  }

/**************************************************************************************************
   Main page output
 **************************************************************************************************/

  $subtitle = isset($_REQUEST["cat"]) ? db_value('select cat_name from categories where cat_id='.$_REQUEST["cat"]) : '';
  page_header(str('VIEW_PHOTO'), $subtitle,'',1,false,'',MEDIA_TYPE_PHOTO);

  if( category_count(MEDIA_TYPE_PHOTO)==1 || isset($_REQUEST["cat"]) )
    display_photo_menu($_REQUEST["cat"]);
  elseif ( isset($_REQUEST["subcat"]) )
    display_categories('photo.php', MEDIA_TYPE_PHOTO, $_REQUEST["subcat"], page_hist_previous());
  else
    display_categories('photo.php', MEDIA_TYPE_PHOTO, 0, page_hist_previous());

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
