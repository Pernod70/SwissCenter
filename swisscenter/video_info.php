<?php
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor
 *************************************************************************************************/

  require_once( realpath(dirname(__FILE__).'/base/page.php'));
  require_once( realpath(dirname(__FILE__).'/base/categories.php'));
  require_once( realpath(dirname(__FILE__).'/base/rating.php'));
  require_once( realpath(dirname(__FILE__).'/base/playlist.php'));

  
/**************************************************************************************************
   Main page output
   *************************************************************************************************/

  $movie = $_REQUEST["movie"];
  
  page_header( str('MOVIE_INFO') ,'');
    
  debug($movie);
  
  $info      = db_toarray("select * from movies where file_id=$movie");
  $directors = db_toarray("select d.director_name from directors_of_movie dom, directors d where dom.director_id = d.director_id and dom.movie_id=$movie");
  $actors    = db_toarray("select a.actor_name from actors_in_movie aim, actors a where aim.actor_id = a.actor_id and aim.movie_id=$movie");
  $genres    = db_toarray("select g.genre_name from genres_of_movie gom, genres g where gom.genre_id = g.genre_id and gom.movie_id=$movie");
  
  debug($info);
  debug($directors);
  debug($actors);
  debug($genres);
  
  


/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
