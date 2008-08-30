<?php
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor
 *************************************************************************************************/

  require_once( realpath(dirname(__FILE__).'/base/search.php'));

  $column        = $_REQUEST["sort"];
  $title         = str('BROWSE_'.strtoupper($column));
  $joined_tables = get_rating_join().viewed_join(MEDIA_TYPE_DVD);
  $history       = search_hist_most_recent();
  
  switch ($column)
  {
    case "title":
    case "year":
      $column = $column;
      break;
    case "genre":
    case "actor":
    case "director":
      $column = $column."_name";
      break;
    case "certificate":
      $column = "IFNULL(media_cert.name,unrated_cert.name)";
      break;
  }
  
  // Only join tables that are actually required
  if ($column == 'director_name' || strpos($history["sql"],'director_name like') > 0)
    $joined_tables .= 'left outer join directors_of_movie dom on media.file_id = dom.movie_id '.
                      'left outer join directors d on dom.director_id = d.director_id ';
  if ($column == 'actor_name' || strpos($history["sql"],'actor_name like') > 0)
    $joined_tables .= 'left outer join actors_in_movie aim on media.file_id = aim.movie_id '.
                      'left outer join actors a on aim.actor_id = a.actor_id ';
  if ($column == 'genre_name' || strpos($history["sql"],'genre_name like') > 0)
    $joined_tables .= 'left outer join genres_of_movie gom on media.file_id = gom.movie_id '.
                      'left outer join genres g on gom.genre_id = g.genre_id ';

  search_media_page( str('WATCH_DVD'), $title, MEDIA_TYPE_DVD, $joined_tables, $column, 'video_dvd_selected.php');
  
/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
