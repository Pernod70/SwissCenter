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
  // Updates the language list, and assigns them to the given movie
  // ----------------------------------------------------------------------------------------

  function scdb_add_languages ( $movies, $items )
  {
    if (! is_array($items))
      $items = array($items);

    if (! is_array($movies))
      $movies = array($movies);

    send_to_log(8,'Storing these languages:',$items);

    foreach ($items as $lang)
    {
      if (!empty($lang))
      {
        $lang = db_escape_str(rtrim(ltrim($lang)));

        $cnt = db_value("select count(*) from languages where language='$lang'");
        if (!empty($lang) && $cnt==0)
          db_sqlcommand("insert into languages values (0,'$lang')",false);

        $lang_id = db_value("select language_id from languages where language='$lang'");
        foreach ($movies as $movie_id)
          db_sqlcommand("insert into languages_of_movie values ($movie_id, $lang_id)",false);
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
    {
      if (count($columns)>0)
        db_sqlcommand("UPDATE movies SET ".db_array_to_set_list($columns)." WHERE file_id=$movie_id",false);
      // Set columns used to sort
      db_sqlcommand("UPDATE movies SET sort_title=trim_article(title,(SELECT `value` FROM `system_prefs` WHERE `name`='IGNORE_ARTICLES')) WHERE file_id=$movie_id",false);
      // Determine whether any details other than title are available
      db_sqlcommand("UPDATE movies SET details_available='".(scdb_movie_details_available($movie_id) ? 'Y' : 'N')."' WHERE file_id=$movie_id",false);
    }
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
  // Updates the language list, and assigns them to the given movie
  // ----------------------------------------------------------------------------------------

  function scdb_add_tv_languages ( $tv, $items )
  {
    if (! is_array($items))
      $items = array($items);

    if (! is_array($tv))
      $tv = array($tv);

    send_to_log(8,'Storing these languages:',$items);

    foreach ($items as $lang)
    {
      if (!empty($lang))
      {
        $lang = db_escape_str(rtrim(ltrim($lang)));

        $cnt = db_value("select count(*) from languages where language='$lang'");
        if (!empty($lang) && $cnt==0)
          db_sqlcommand("insert into languages values (0,'$lang')",false);

        $lang_id = db_value("select language_id from languages where language='$lang'");
        foreach ($tv as $tv_id)
          db_sqlcommand("insert into languages_of_tv values ($tv_id, $lang_id)",false);
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
    {
      if (count($columns)>0)
        db_sqlcommand("UPDATE tv SET ".db_array_to_set_list($columns)." WHERE file_id=$tv_id",false);
      // Set columns used to sort
      db_sqlcommand("UPDATE tv SET sort_title=trim_article(title,(SELECT `value` FROM `system_prefs` WHERE `name`='IGNORE_ARTICLES')) WHERE file_id=$tv_id",false);
      db_sqlcommand("UPDATE tv SET sort_programme=trim_article(programme,(SELECT `value` FROM `system_prefs` WHERE `name`='IGNORE_ARTICLES')) WHERE file_id=$tv_id",false);
      // Determine whether any details other than title are available
      db_sqlcommand("UPDATE tv SET details_available='".(scdb_tv_details_available($tv_id) ? 'Y' : 'N')."' WHERE file_id=$tv_id",false);
    }
  }

  // ----------------------------------------------------------------------------------------
  // Identifies and removed orphaned rows within the database
  // ----------------------------------------------------------------------------------------

  function scdb_remove_orphans ()
  {
    $actors    = db_toarray("select a.actor_id from actors a
                             left outer join actors_in_movie aim on (a.actor_id = aim.actor_id)
                             left outer join actors_in_tv ait on (a.actor_id = ait.actor_id)
                             where movie_id is null and tv_id is null;");

    $directors = db_toarray("select d.director_id from directors d
                             left outer join directors_of_movie dom on (d.director_id = dom.director_id)
                             left outer join directors_of_tv dot on (d.director_id = dot.director_id)
                             where movie_id is null and tv_id is null;");

    $genres    = db_toarray("select g.genre_id from genres g
                             left outer join genres_of_movie gom on (g.genre_id = gom.genre_id)
                             left outer join genres_of_tv got on (g.genre_id = got.genre_id)
                             where movie_id is null and tv_id is null;");

    $languages = db_toarray("select l.language_id from languages l
                             left outer join languages_of_movie lom on (l.language_id = lom.language_id)
                             left outer join languages_of_tv lot on (l.language_id = lot.language_id)
                             where movie_id is null and tv_id is null;");

    foreach ($actors as $row)
      db_sqlcommand("delete from actors where actor_id = ".$row["ACTOR_ID"],false);

    foreach ($directors as $row)
      db_sqlcommand("delete from directors where director_id = ".$row["DIRECTOR_ID"],false);

    foreach ($genres as $row)
      db_sqlcommand("delete from genres where genre_id = ".$row["GENRE_ID"],false);

    foreach ($languages as $row)
      db_sqlcommand("delete from languages where language_id = ".$row["LANGUAGE_ID"],false);
  }

  // ----------------------------------------------------------------------------------------
  // Determine whether any details other than title are available
  // ----------------------------------------------------------------------------------------

  function scdb_movie_details_available( $movie_id )
  {
    $attribs   = implode('',db_row("select year, certificate, synopsis from movies where file_id=$movie_id"));
    $actors    = db_value("select count(actor_id) from actors_in_movie where movie_id=$movie_id");
    $directors = db_value("select count(director_id) from directors_of_movie where movie_id=$movie_id");
    $genres    = db_value("select count(genre_id) from genres_of_movie where movie_id=$movie_id");
    $languages = db_value("select count(language_id) from languages_of_movie where movie_id=$movie_id");
    if (empty($attribs) && $actors==0 && $directors==0 && $genres==0 && $languages==0)
      return false;
    else
      return true;
  }

  function scdb_tv_details_available( $tv_id )
  {
    $attribs   = implode('',db_row("select year, certificate, synopsis from tv where file_id=$tv_id"));
    $actors    = db_value("select count(actor_id) from actors_in_tv where tv_id=$tv_id");
    $directors = db_value("select count(director_id) from directors_of_tv where tv_id=$tv_id");
    $genres    = db_value("select count(genre_id) from genres_of_tv where tv_id=$tv_id");
    $languages = db_value("select count(language_id) from languages_of_tv where tv_id=$tv_id");
    if (empty($attribs) && $actors==0 && $directors==0 && $genres==0 && $languages==0)
      return false;
    else
      return true;
  }

/**************************************************************************************************
                                               End of file
***************************************************************************************************/
?>
