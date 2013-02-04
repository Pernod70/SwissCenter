<?php
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor
 *************************************************************************************************/

  require_once( realpath(dirname(__FILE__).'/base/search.php'));

  $column        = $_REQUEST["sort"];
  $joined_tables = get_rating_join().viewed_join(MEDIA_TYPE_VIDEO);
  $articles      = get_sys_pref('IGNORE_ARTICLES');

  $search = array();
  switch ($column)
  {
    case "title":
      $title  = str('TITLE');
      $search = array("display" => "title",
                      "info"    => "year",
                      "order"   => "trim_article(display,'$articles')");
      break;
    case "year":
      $title  = str('TITLE');
      $search = array("display" => "title",
                      "info"    => "year",
                      "order"   => "info desc, trim_article(display,'$articles')");
      break;
    case "genre":
    case "actor":
    case "director":
      $title  = str(mb_strtoupper($column));
      $search = array("display" => $column."_name",
                      "info"    => "count(distinct synopsis)",
                      "order"   => "display");
      break;
    case "certificate":
      $title  = str(mb_strtoupper($column));
//    $search = array("display" => "IFNULL((select name from certificates where rank >= media_cert.rank and scheme = '".get_rating_scheme_name()."' order by rank limit 1),(select name from certificates where rank >= unrated_cert.rank and scheme = '".get_rating_scheme_name()."' order by rank limit 1))",
      $search = array("display" => "IFNULL(media_cert.name,unrated_cert.name)",
                      "info"    => "count(distinct synopsis)",
                      "order"   => "display");
      break;
    case "rating":
      $title  = str('TITLE');
      $search = array("display" => "title",
                      "info"    => "truncate(external_rating_pc/10,1)",
                      "order"   => "info desc, trim_article(display,'$articles')");
      break;
    case "discovered":
      $title  = str('TITLE');
      $search = array("display" => "title",
                      "info"    => "date_format(discovered,'".get_sys_pref('DATE_FORMAT','%d%b%y')."')",
                      "order"   => "discovered desc");
      break;
    case "timestamp":
      $title  = str('TITLE');
      $search = array("display" => "title",
                      "info"    => "date_format(timestamp,'".get_sys_pref('DATE_FORMAT','%d%b%y')."')",
                      "order"   => "timestamp desc");
      break;
  }

  // Only join tables that are actually required
  $history = page_hist_current();
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
