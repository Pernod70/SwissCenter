<?php
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor
 *************************************************************************************************/

 require_once( realpath(dirname(__FILE__).'/mysql.php'));
 
  // ----------------------------------------------------------------------------------------
  // Updates the actors list, and assigns them to the given movie
  // ----------------------------------------------------------------------------------------

  function scdb_add_actors ( $movies, $items )
  {
    if (! is_array($items))
      $items = array($items);
      
    if (! is_array($movies))
      $movies = array($movies);

    send_to_log(8,'Storing these actors:',$items);

    foreach ($items as $actor)
    {
      if (!empty($actor))
      {
        $actor = db_escape_str(rtrim(ltrim($actor)));
        
        $cnt = db_value("select count(*) from actors where actor_name='$actor'");
        if (!empty($actor) && $cnt==0)
          db_sqlcommand("insert into actors values (0,'$actor')",false);
  
        $actor_id = db_value("select actor_id from actors where actor_name='$actor'");
        foreach ($movies as $movie_id)
          db_sqlcommand("insert into actors_in_movie values ($movie_id, $actor_id)",false);
      }
    }
  }

  // ----------------------------------------------------------------------------------------
  // Updates the directors list, and assigns them to the given movie
  // ----------------------------------------------------------------------------------------

  function scdb_add_directors ( $movies, $items )
  {
    if (! is_array($items))
      $items = array($items);
      
    if (! is_array($movies))
      $movies = array($movies);
      
    send_to_log(8,'Storing these directors:',$items);
    
    foreach ($items as $dir)
    {
      if (! empty($dir))
      {
        $dir = db_escape_str(rtrim(ltrim($dir)));
        
        $cnt = db_value("select count(*) from directors where director_name='$dir'");
        if (!empty($dir) && $cnt==0)
          db_sqlcommand("insert into directors values (0,'$dir')",false);
  
        $dir_id = db_value("select director_id from directors where director_name='$dir'");
        foreach ($movies as $movie_id)
          db_sqlcommand("insert into directors_of_movie values ($movie_id, $dir_id)",false);
      }
    }
  }

  // ----------------------------------------------------------------------------------------
  // Updates the genre list, and assigns them to the given movie
  // ----------------------------------------------------------------------------------------

  function scdb_add_genres ( $movies, $items )
  {
    if (! is_array($items))
      $items = array($items);

    if (! is_array($movies))
      $movies = array($movies);

    send_to_log(8,'Storing these genres:',$items);

    foreach ($items as $genre)
    {
      if (!empty($genre))
      {
        $genre = db_escape_str(rtrim(ltrim($genre)));
        
        $cnt = db_value("select count(*) from genres where genre_name='$genre'");
        if (!empty($genre) && $cnt==0)
          db_sqlcommand("insert into genres values (0,'$genre')",false);
  
        $genre_id = db_value("select genre_id from genres where genre_name='$genre'");        
        foreach ($movies as $movie_id)
          db_sqlcommand("insert into genres_of_movie values ($movie_id, $genre_id)",false);
      }
    }
  }

  // ----------------------------------------------------------------------------------------
  // Updates the MOVIE row in the database
  // ----------------------------------------------------------------------------------------

  function scdb_set_movie_attribs ( $movies, $columns )
  {
    if (! is_array($movies))
      $movies = array($movies);

    send_to_log(8,'Storing:',$columns);

    foreach ($movies as $movie_id)
      db_sqlcommand("update movies set ".db_array_to_set_list($columns)." where file_id=".$movie_id,false);
  }   
 
  // ----------------------------------------------------------------------------------------
  // Updates the actors list, and assigns them to the given tv series
  // ----------------------------------------------------------------------------------------

  function scdb_add_tv_actors ( $tv, $items )
  {
    if (! is_array($items))
      $items = array($items);
      
    if (! is_array($tv))
      $tv = array($tv);

    send_to_log(8,'Storing these actors:',$items);

    foreach ($items as $actor)
    {
      if (!empty($actor))
      {
        $actor = db_escape_str(rtrim(ltrim($actor)));
        
        $cnt = db_value("select count(*) from actors where actor_name='$actor'");
        if (!empty($actor) && $cnt==0)
          db_sqlcommand("insert into actors values (0,'$actor')",false);
  
        $actor_id = db_value("select actor_id from actors where actor_name='$actor'");
        foreach ($tv as $tv_id)
          db_sqlcommand("insert into actors_in_tv values ($tv_id, $actor_id)",false);
      }
    }
  }

  // ----------------------------------------------------------------------------------------
  // Updates the directors list, and assigns them to the given tv series
  // ----------------------------------------------------------------------------------------

  function scdb_add_tv_directors ( $tv, $items )
  {
    if (! is_array($items))
      $items = array($items);
      
    if (! is_array($tv))
      $tv = array($tv);
      
    send_to_log(8,'Storing these directors:',$items);
    
    foreach ($items as $dir)
    {
      if (! empty($dir))
      {
        $dir = db_escape_str(rtrim(ltrim($dir)));
        
        $cnt = db_value("select count(*) from directors where director_name='$dir'");
        if (!empty($dir) && $cnt==0)
          db_sqlcommand("insert into directors values (0,'$dir')",false);
  
        $dir_id = db_value("select director_id from directors where director_name='$dir'");
        foreach ($tv as $tv_id)
          db_sqlcommand("insert into directors_of_tv values ($tv_id, $dir_id)",false);
      }
    }
  }

  // ----------------------------------------------------------------------------------------
  // Updates the genre list, and assigns them to the given tv series
  // ----------------------------------------------------------------------------------------

  function scdb_add_tv_genres ( $tv, $items )
  {
    if (! is_array($items))
      $items = array($items);

    if (! is_array($tv))
      $tv = array($tv);

    send_to_log(8,'Storing these genres:',$items);

    foreach ($items as $genre)
    {
      if (!empty($genre))
      {
        $genre = db_escape_str(rtrim(ltrim($genre)));
        
        $cnt = db_value("select count(*) from genres where genre_name='$genre'");
        if (!empty($genre) && $cnt==0)
          db_sqlcommand("insert into genres values (0,'$genre')",false);
  
        $genre_id = db_value("select genre_id from genres where genre_name='$genre'");        
        foreach ($tv as $tv_id)
          db_sqlcommand("insert into genres_of_tv values ($tv_id, $genre_id)",false);
      }
    }
  }

  // ----------------------------------------------------------------------------------------
  // Updates the TV row in the database
  // ----------------------------------------------------------------------------------------

  function scdb_set_tv_attribs ( $tv, $columns )
  {
    if (! is_array($tv))
      $tv = array($tv);

    send_to_log(8,'Storing:',$columns);

    foreach ($tv as $tv_id)
      db_sqlcommand("update tv set ".db_array_to_set_list($columns)." where file_id=".$tv_id,false);
  }   
  
  // ----------------------------------------------------------------------------------------
  // Identifies and removed orphaned rows within the database
  // ----------------------------------------------------------------------------------------
  
  function scdb_remove_orphans ()
  {
    $actors    = db_toarray("select a.actor_id
                             from actors a left outer join actors_in_movie aim on (a.actor_id = aim.actor_id)
                             where movie_id is null;");
    
    $actors    = array_merge($actors, db_toarray("select a.actor_id
                             from actors a left outer join actors_in_tv ait on (a.actor_id = ait.actor_id)
                             where tv_id is null;"));

    $directors = db_toarray("select d.director_id
                          from directors d left outer join directors_of_movie dom on (d.director_id = dom.director_id)
                          where movie_id is null;");
    
    $directors = array_merge($directors, db_toarray("select d.director_id
                          from directors d left outer join directors_of_tv dot on (d.director_id = dot.director_id)
                          where tv_id is null;"));

    $genres    = db_toarray("select g.genre_id
                             from genres g left outer join genres_of_movie gom on (g.genre_id = gom.genre_id)
                             where movie_id is null;");
    
    $genres    = array_merge($genres, db_toarray("select g.genre_id
                             from genres g left outer join genres_of_tv got on (g.genre_id = got.genre_id)
                             where tv_id is null;"));

    foreach ($actors as $row)
      db_sqlcommand("delete from actors where actor_id = ".$row["ACTOR_ID"],false);

    foreach ($directors as $row)
      db_sqlcommand("delete from directors where director_id = ".$row["DIRECTOR_ID"],false);

    foreach ($genres as $row)
      db_sqlcommand("delete from genres where genre_id = ".$row["GENRE_ID"],false);
  }
  
/**************************************************************************************************
                                               End of file
***************************************************************************************************/
?>
