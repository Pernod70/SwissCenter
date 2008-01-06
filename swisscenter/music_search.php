<?php
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor
 *************************************************************************************************/

  require_once( realpath(dirname(__FILE__).'/base/search.php'));

  $column        = $_REQUEST["sort"];
  $joined_tables = get_rating_join().viewed_join(MEDIA_TYPE_MUSIC);

  switch ($column)
  {
    case "album":
    case "band":
    case "artist":
    case "genre":
    case "year":
      $title = str(strtoupper(($column)));
      break;
    case "title":
      $title = str('TRACK_NAME');
      break;
  }

  search_media_page( str('LISTEN_MUSIC'), $title, MEDIA_TYPE_MUSIC, $joined_tables , $column, 'music_selected.php');

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
