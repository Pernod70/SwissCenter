<?php
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor
 *************************************************************************************************/

  require_once( realpath(dirname(__FILE__).'/base/search.php'));

  // Check page parameters
  $column        = $_REQUEST["sort"];
  $joined_tables = get_rating_join().viewed_join(MEDIA_TYPE_PHOTO);
  $articles      = get_sys_pref('IGNORE_ARTICLES');

  $search = array();
  switch ($column)
  {
    case "filename":
      $title  = str('PHOTO_TITLE');
      $search = array("display" => "filename",
                      "info"    => "date_format(media.timestamp,'%d%b%y')",
                      "order"   => "trim_article(display,'$articles')");
      break;
    case "title":
      $title  = str('PHOTO_ALBUM');
      $search = array("display" => "title",
                      "info"    => "count(filename)",
                      "order"   => "display");
      break;
    case "iptc_byline":
    case "iptc_caption":
    case "iptc_city":
    case "iptc_country":
    case "iptc_keywords":
    case "iptc_location":
    case "iptc_province_state":
    case "iptc_suppcategory":
    case "xmp_rating":
      $title  = str(strtoupper(($column)));
      $search = array("display" => $column,
                      "info"    => "count(filename)",
                      "order"   => "display");
      break;
    case "discovered":
      $title  = str('PHOTO_TITLE');
      $search = array("display" => "filename",
                      "info"    => "date_format(media.discovered,'".get_sys_pref('DATE_FORMAT','%d%b%y')."')",
                      "order"   => "media.discovered desc");
      break;
    case "timestamp":
      $title  = str('PHOTO_TITLE');
      $search = array("display" => "filename",
                      "info"    => "date_format(media.timestamp,'".get_sys_pref('DATE_FORMAT','%d%b%y')."')",
                      "order"   => "media.timestamp desc");
      break;
    default :
      send_to_log(1,'Unknown $column in photo_search.php');
      page_error('Unexpected error - please see log for details');
      break;
  }

  // Only join tables that are actually required
  $history = page_hist_current();
  if ($search["display"] == 'title' || strpos($history["sql"],'title like') > 0)
    $joined_tables .= 'left outer join photo_albums pa on media.dirname = pa.dirname ';

  search_media_page( str('VIEW_PHOTO'), $title, MEDIA_TYPE_PHOTO, $joined_tables, $search, 'photo_selected.php' );

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
