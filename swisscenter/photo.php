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

  function display_photo_menu($cat_id)
  {
    if(empty($cat_id))
      search_hist_init( 'photo.php', get_rating_filter().filter_get_predicate() );
    else
      search_hist_init( 'photo.php?cat='.$cat_id, category_select_sql($cat_id, 2).get_rating_filter().filter_get_predicate() );

    echo '<center>'.str('SELECT_OPTION').'</center><p>';

    $menu = new menu();
    if (get_sys_pref('browse_photo_album_enabled','YES') == 'YES')
      $menu->add_item( str('BROWSE_PHOTO_ALBUM') ,"photo_search.php?sort=title",true);
    if (get_sys_pref('browse_photo_title_enabled','YES') == 'YES')
      $menu->add_item( str('BROWSE_PHOTO_TITLE') ,"photo_search.php?sort=filename",true);
    if (get_sys_pref('browse_photo_filesystem_enabled','YES') == 'YES') 
      $menu->add_item( str('BROWSE_FILESYSTEM')  ,"photo_browse.php",true);
    
    if ($menu->num_items() == 1)
    {
      search_hist_init( 'photo.php', category_select_sql($cat_id, 2).get_rating_filter() );
      header('Location: '.server_address().$menu->item_url(0));
    } 
    else
    {
      $menu->display();
    }
    
    $buttons = array();
    $buttons[] = array('text' => str('QUICK_PLAY'),'url'  => quick_play_link(MEDIA_TYPE_PHOTO,$_SESSION["history"][0]["sql"]));
    $buttons[] = array('text' => str('FILTER'),'url'  => 'get_filter.php?return='.urlencode('photo.php?cat='.$cat_id));
    page_footer('photo.php', $buttons);
  }

/**************************************************************************************************
   Main page output
 *************************************************************************************************/

  page_header(str('VIEW_PHOTO'),'');

  if( category_count(MEDIA_TYPE_PHOTO)==1 || !empty($_REQUEST["cat"]) )
    display_photo_menu($_REQUEST["cat"]);
  else
    display_categories('photo.php', 2);

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
