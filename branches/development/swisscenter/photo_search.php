<?
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor
 *************************************************************************************************/

  require_once( realpath(dirname(__FILE__).'/base/search.php'));

  // Check page parameters
  $column        = $_REQUEST["sort"];
  $joined_tables = "inner join photo_albums pa on media.dirname like concat(pa.dirname,'%') ";

  switch ($column)
  {
    case "filename":
        $title       = str('PHOTO_TITLE');
        break;
    case "title":
        $title       = str('PHOTO_ALBUM');
        break;
    default :
        send_to_log('Unknown $column in photo_album_search.php');
        page_error('Unexpected error - please see log for details');
        break;
  }

  search_media_page( str('VIEW_PHOTO'), $title, 'photos', $joined_tables, $column, 'photo_selected.php' );

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
