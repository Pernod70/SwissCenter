<?php
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor
 *************************************************************************************************/

  require_once( realpath(dirname(__FILE__).'/base/search.php'));

  $column        = $_REQUEST["sort"];
  $joined_tables = get_rating_join().viewed_join(MEDIA_TYPE_MUSIC);

  $search = array();
  switch ($column)
  {
    case "album":
    case "band":
    case "artist":
    case "genre":
      $title  = str(strtoupper(($column)));
      $search = array("display" => $column,
                      "info"    => "count(filename)",
                      "order"   => "display");
      break;
    case "year":
      $title  = str(strtoupper(($column)));
      $search = array("display" => $column,
                      "info"    => "count(filename)",
                      "order"   => "display desc");
      break;
    case "title":
      $title  = str('TRACK_NAME');
      $search = array("display" => "title",
                      "info"    => "year",
                      "order"   => "display");
      break;
    case "discovered":
      $title  = str('TRACK_NAME');
      $search = array("display" => "title",
                      "info"    => "date_format(discovered,'%d%b%y')",
                      "order"   => "discovered desc");
      break;
    case "timestamp":
      $title  = str('TRACK_NAME');
      $search = array("display" => "title",
                      "info"    => "date_format(timestamp,'%d%b%y')",
                      "order"   => "timestamp desc");
      break;
  }

  search_media_page( str('LISTEN_MUSIC'), $title, MEDIA_TYPE_MUSIC, $joined_tables, $search, 'music_selected.php');

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
