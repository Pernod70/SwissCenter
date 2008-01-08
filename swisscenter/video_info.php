<?php
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor
 *************************************************************************************************/

  require_once( realpath(dirname(__FILE__).'/base/page.php'));
  require_once( realpath(dirname(__FILE__).'/base/rating.php'));
  require_once( realpath(dirname(__FILE__).'/base/search.php'));

/**************************************************************************************************
   Main page output
 *************************************************************************************************/

  // Get actor/director/genre lists
  if (isset($_REQUEST["tv"]))
  {
    $tv_id = $_REQUEST["tv"];
    $info      = db_toarray("select * from tv where file_id=$tv_id");
    $directors = db_col_to_list("select d.director_name name from directors_of_tv dot, directors d where dot.director_id = d.director_id and dot.tv_id=$tv_id");
    $actors    = db_col_to_list("select a.actor_name name from actors_in_tv ait, actors a where ait.actor_id = a.actor_id and ait.tv_id=$tv_id");
    $genres    = db_col_to_list("select g.genre_name name from genres_of_tv got, genres g where got.genre_id = g.genre_id and got.tv_id=$tv_id");
  }
  else
  {
    $movie_id = $_REQUEST["movie"];
    $info      = db_toarray("select * from movies where file_id=$movie_id");
    $directors = db_col_to_list("select d.director_name name from directors_of_movie dom, directors d where dom.director_id = d.director_id and dom.movie_id=$movie_id");
    $actors    = db_col_to_list("select a.actor_name name from actors_in_movie aim, actors a where aim.actor_id = a.actor_id and aim.movie_id=$movie_id");
    $genres    = db_col_to_list("select g.genre_name name from genres_of_movie gom, genres g where gom.genre_id = g.genre_id and gom.movie_id=$movie_id");
  }
  
  // Save the previous page
  $history = search_hist_pop();
  
  if (!empty($info[0]["YEAR"]))
    page_header( $info[0]["TITLE"].' ('.$info[0]["YEAR"].')' ,'');
  else 
    page_header( $info[0]["TITLE"] );
  
  // Display thumbnail
  $folder_img = file_albumart($info[0]["DIRNAME"].$info[0]["FILENAME"]);

  // Certificate? Get the appropriate image.
  $cert_img = '';
  if (!empty($info[0]["CERTIFICATE"]))
  {
    $scheme   = get_rating_scheme_name();
    $cert_img = img_gen(SC_LOCATION.'images/ratings/'.$scheme.'/'.get_cert_name( get_nearest_cert_in_scheme($info[0]["CERTIFICATE"], $scheme)).'.gif', convert_x(250), convert_y(180));
  }
  
  echo '<p><table width="100%" cellpadding=0 cellspacing=0 border=0';
  // Is there a picture for us to display?
  if (!empty($folder_img) )
    echo '<tr><td valign=top width="'.convert_x(280).'" align="left">
              '.img_gen($folder_img,280,550).'<br><center>'.$cert_img.'</center>
              </td><td width="'.convert_x(20).'"></td>';
  echo       '<td valign="top">';
  
  // Cast
  if ($actors)
  {
    echo '<b>'.font_tags(32,'PAGE_TEXT_BOLD_COLOUR').str('ACTOR').':</font></b><br>';
    echo font_tags(32).implode(', ', $actors).'</font><br><br>';
  }
        
  // Director
  if ($directors)
  {
    echo '<b>'.font_tags(32,'PAGE_TEXT_BOLD_COLOUR').str('DIRECTOR').':</font></b><br>';
    echo font_tags(32).implode(', ', $directors).'</font><br><br>';
  }
        
  // Genres
  if ($genres)
  {
    echo '<b>'.font_tags(32,'PAGE_TEXT_BOLD_COLOUR').str('GENRE').':</font></b><br>';
    echo font_tags(32).implode(', ', $genres).'</font><br><br>';
  }
  
  echo '</td></table>';

  page_footer( url_add_param($history["url"], 'add','Y') );

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
