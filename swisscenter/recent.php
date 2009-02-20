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

function redirect_to_browse( $media_type )
{
  $num_days = get_sys_pref("RECENT_DATE_LIMIT",14);
  filter_set(str('RECENTLY_ADDED'), " and media.discovered > ('".db_datestr()."' - interval $num_days day)" );
  search_hist_init( 'recent.php', get_rating_filter().filter_get_predicate() );
  switch ($media_type)
  {
    case MEDIA_TYPE_VIDEO:
      header("Location: /video_search.php?sort=title");
      break;
    case MEDIA_TYPE_TV:
      header("Location: /music_search.php?sort=album");
      break;
    case MEDIA_TYPE_MUSIC:
      header("Location: /music_search.php?sort=album");
      break;
    case MEDIA_TYPE_PHOTO:
      header("Location: /photo_search.php?sort=title");
      break;
  }
}

function show_menu()
{
  page_header( str('RECENT_MEDIA'), '','',1,false,'',PAGE_RECENT);

  echo '<p>';
  $menu = new menu();
  $menu->add_item( str('RECENT_VIDEO') ,"recent.php?type=".MEDIA_TYPE_VIDEO,true);
//  $menu->add_item( str('RECENT_TV')    ,"recent.php?type=".MEDIA_TYPE_TV,true);
  $menu->add_item( str('RECENT_MUSIC') ,"recent.php?type=".MEDIA_TYPE_MUSIC,true);
  $menu->add_item( str('RECENT_PHOTO') ,"recent.php?type=".MEDIA_TYPE_PHOTO,true);
  $menu->display(1, style_value("MENU_RECENT_WIDTH"), style_value("MENU_RECENT_ALIGN"));
  page_footer('index.php', $buttons);
}

/**
 * Main page logic
 */

if( isset($_REQUEST["type"]))
  redirect_to_browse( $_REQUEST["type"]);
else
  show_menu();

/**************************************************************************************************
  End of file
**************************************************************************************************/
?>
