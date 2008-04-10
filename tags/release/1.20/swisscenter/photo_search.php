<?php
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor
 *************************************************************************************************/

  require_once( realpath(dirname(__FILE__).'/base/search.php'));

  // Check page parameters
  $column        = $_REQUEST["sort"];
  $joined_tables = " left outer join photo_albums pa on media.dirname like concat(pa.dirname,'%') ".get_rating_join().viewed_join(MEDIA_TYPE_PHOTO);

  switch ($column)
  {
    case "filename":
        $title       = str('PHOTO_TITLE');
        break;
    case "title":
        $title       = str('PHOTO_ALBUM');
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
        $title       = str(strtoupper(($column)));
        break;
    default :
        send_to_log(1,'Unknown $column in photo_album_search.php');
        page_error('Unexpected error - please see log for details');
        break;
  }

  search_media_page( str('VIEW_PHOTO'), $title, MEDIA_TYPE_PHOTO, $joined_tables, $column, 'photo_selected.php' );

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
