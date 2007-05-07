<?
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor
 *************************************************************************************************/

  require_once( realpath(dirname(__FILE__).'/base/search.php'));

  $column    = $_REQUEST["sort"];

  switch ($column)
  {
    case "album":
    case "artist":
    case "genre":
    case "year":
      $title = str(strtoupper(($column)));
      break;
    case "title":
      $title = str('TRACK_NAME');
      break;
  }

  search_media_page( str('LISTEN_MUSIC'), $title, 'mp3s', '', $column, 'music_selected.php');

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
