<?
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor
 *************************************************************************************************/

  require_once("base/search.php");

  // Check page parameters
  $column    = $_REQUEST["sort"];

  switch ($column)
  {
    case "filename":
        $title      = str('TITLE');
        $table      = 'photos';
        $choose_url = 'photo_selected.php';
        break;
    case "title":
        $title      = str('PHOTO_ALBUM');
        $table      = 'photo_albums';
        $choose_url = 'photo_album_selected.php';
        break;
    default :
        send_to_log('Unknown $column in photo_album_search.php');
        page_error('Unexpected error - please see log for details');
        break;
  }

  search_media_page( str('VIEW_PHOTO'), $title, $table, '', $column, $choose_url )


/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
