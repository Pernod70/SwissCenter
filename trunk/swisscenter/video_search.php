<?php
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor
 *************************************************************************************************/

  require_once( realpath(dirname(__FILE__).'/base/search.php'));

  $column        = $_REQUEST["sort"];
  $joined_tables = get_rating_join().viewed_join(MEDIA_TYPE_VIDEO);

  $search = array();
  switch ($column)
  {
    case "title":
      $title  = str('TITLE');
      $search = array("display" => "title",
                      "info"    => "year",
                      "order"   => "display");
      break;
    case "year":
      $title  = str('TITLE');
      $search = array("display" => "title",
                      "info"    => "year",
                      "order"   => "info desc, display");
      break;
    case "genre":
    case "actor":
    case "director":
      $title  = str(strtoupper(($column)));
      $search = array("display" => $column."_name",
                      "info"    => "count(distinct synopsis)",
                      "order"   => "display");
      break;
    case "certificate":
      $title  = str(strtoupper(($column)));
      $search = array("display" => "IFNULL(media_cert.name,unrated_cert.name)",
                      "info"    => "count(distinct synopsis)",
                      "order"   => "display");
      break;
    case "rating":
      $title  = str('TITLE');
      $search = array("display" => "title",
                      "info"    => "truncate(external_rating_pc/10,1)",
                      "order"   => "info desc, display");
      break;
    case "discovered":
      $title  = str('TITLE');
      $search = array("display" => "title",
                      "info"    => "date_format(discovered,'%d%b%y')",
                      "order"   => "discovered desc");
      break;
    case "timestamp":
      $title  = str('TITLE');
      $search = array("display" => "title",
                      "info"    => "date_format(timestamp,'%d%b%y')",
                      "order"   => "timestamp desc");
      break;
  }

  // Only join tables that are actually required
  $history = search_hist_most_recent();
  if ($search["display"] == 'director_name' || strpos($history["sql"],'director_name like') > 0)
    $joined_tables .= 'left outer join directors_of_movie dom on media.file_id = dom.movie_id '.
                      'left outer join directors d on dom.director_id = d.director_id ';
  if ($search["display"] == 'actor_name' || strpos($history["sql"],'actor_name like') > 0)
    $joined_tables .= 'left outer join actors_in_movie aim on media.file_id = aim.movie_id '.
                      'left outer join actors a on aim.actor_id = a.actor_id ';
  if ($search["display"] == 'genre_name' || strpos($history["sql"],'genre_name like') > 0)
    $joined_tables .= 'left outer join genres_of_movie gom on media.file_id = gom.movie_id '.
                      'left outer join genres g on gom.genre_id = g.genre_id ';

  search_media_page( str('WATCH_MOVIE'), $title, MEDIA_TYPE_VIDEO, $joined_tables, $search, 'video_selected.php');

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
