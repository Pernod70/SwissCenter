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
    case "sort_album":
    case "sort_band":
    case "sort_artist":
      $title  = str(str_replace('sort_','',$column));
      $search = array("display" => $column,
                      "info"    => "count(filename)",
                      "order"   => "display");
      break;
    case "genre":
    case "mood":
    case "composer":
      $title  = str($column);
      $search = array("display" => $column,
                      "info"    => "count(filename)",
                      "order"   => "display");
      break;
    case "year":
      $title  = str($column);
      $search = array("display" => $column,
                      "info"    => "count(filename)",
                      "order"   => "display desc");
      break;
    case "sort_title":
      $title  = str('TRACK_NAME');
      $search = array("display" => "sort_title",
                      "info"    => "year",
                      "order"   => "sort_title");
      break;
    case "discovered":
      $title  = str('TRACK_NAME');
      $search = array("display" => "sort_title",
                      "info"    => "date_format(discovered,'".get_sys_pref('DATE_FORMAT','%d%b%y')."')",
                      "order"   => "discovered desc");
      break;
    case "timestamp":
      $title  = str('TRACK_NAME');
      $search = array("display" => "sort_title",
                      "info"    => "date_format(timestamp,'".get_sys_pref('DATE_FORMAT','%d%b%y')."')",
                      "order"   => "timestamp desc");
      break;
  }

  search_media_page( str('LISTEN_MUSIC'), $title, MEDIA_TYPE_MUSIC, $joined_tables, $search, 'music_selected.php');

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
