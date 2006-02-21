<?
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor
 *************************************************************************************************/

  require_once("base/page.php");
  require_once("base/categories.php");
  require_once("base/rating.php");
  require_once("base/playlist.php");
  require_once("base/search.php");

  function display_photo_menu($cat_id)
  {
    if(empty($cat_id))
      search_hist_init( 'photo.php', get_rating_filter() );
    else
      search_hist_init( 'photo.php?cat='.$cat_id, category_select_sql($cat_id, 1).get_rating_filter() );

    echo '<center>'.str('SELECT_OPTION').'</center><p>';

    $menu = new menu();
    $menu->add_item( str('BROWSE_PHOTO_ALBUM') ,"photo_search.php?sort=title",true);
    $menu->add_item( str('BROWSE_PHOTO_TITLE') ,"photo_search.php?sort=filename",true);
    $menu->add_item( str('BROWSE_FILESYSTEM')  ,"photo_browse.php",true);
    $menu->display();
    
    if(!empty($cat_id))
      page_footer('photo.php', array(array('text'=>str('QUICK_PLAY'), 'url' => quick_play_link(MEDIA_TYPE_PHOTO,$_SESSION["history"][0][sql]))));
    else
      page_footer('photo.php', array(array('text'=>str('QUICK_PLAY'), 'url' => quick_play_link(MEDIA_TYPE_PHOTO,$_SESSION["history"][0][sql]))));
  }

/**************************************************************************************************
   Main page output
 *************************************************************************************************/

  page_header(str('VIEW_PHOTO'),'');
  $cat_id = $_REQUEST["cat"];
  
  if( !empty($cat_id) )
    display_photo_menu($cat_id);
  else
    display_categories('photo.php', 2);

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
