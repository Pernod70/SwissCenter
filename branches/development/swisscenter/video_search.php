<?
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor
 *************************************************************************************************/

  require_once("base/search.php");

  $column        = $_REQUEST["sort"];
  $title         = str('BROWSE_'.strtoupper($column));
  $joined_tables = 'left outer join directors_of_movie dom on media.file_id = dom.movie_id
                    left outer join genres_of_movie gom on media.file_id = gom.movie_id
                    left outer join actors_in_movie aim on media.file_id = aim.movie_id
                    left outer join actors a on aim.actor_id = a.actor_id
                    left outer join directors d on dom.director_id = d.director_id
                    left outer join genres g on gom.genre_id = g.genre_id';
  
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

  search_media_page( str('WATCH_MOVIE'), $title, 'movies', $joined_tables, $column, 'video_selected.php')

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
