<?php
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor
 *************************************************************************************************/

  require_once( realpath(dirname(__FILE__).'/mysql.php'));
  require_once( realpath(dirname(__FILE__).'/../ext/xml/XPath.class.php'));

  /**
   * Exports the movie details to a file
   *
   */

  function export_video_to_xml ( $file_id )
  {
    $details  = db_toarray("select * from movies where file_id = $file_id");

    // DVD Video details are stored in the parent folder
    if ( strtoupper($details[0]["FILENAME"]) == 'VIDEO_TS.IFO' )
      $filename = rtrim($details[0]["DIRNAME"],'/').".xml";
    else
      $filename = substr($details[0]["DIRNAME"].$details[0]["FILENAME"],0,strrpos($details[0]["DIRNAME"].$details[0]["FILENAME"],'.')).".xml";

    if ( empty($details) )
    {
      send_to_log(5, "Unable to obtain video details for XML export for file_id=$file_id");
      return false;
    }
    elseif ( !is_writable(dirname($filename)) )
    {
      send_to_log(4, "Unable to write video XML file: $filename");
      return false;
    }
    else
    {
      send_to_log(5, 'Saving movie XML:', $filename);
      $options = array(XML_OPTION_CASE_FOLDING => FALSE, XML_OPTION_SKIP_WHITE => TRUE);
      $xml = new XPath(FALSE, $options);
      $xml->importFromString('<?xml version="1.0" encoding="utf-8"?><movie xmlns="http://www.swisscenter.co.uk" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="http://www.swisscenter.co.uk movies.xsd"></movie>');
      $movie_path = '/movie[1]';
      $xml->appendChild($movie_path,'<title>'.utf8_encode(htmlspecialchars($details[0]["TITLE"])).'</title>');
      if ( !empty( $details[0]["SYNOPSIS"] ) )
        $xml->appendChild($movie_path,'<synopsis>'.utf8_encode(htmlspecialchars($details[0]["SYNOPSIS"])).'</synopsis>');

      // Actors
      $actor_list = db_toarray("select actor_name name from actors a, actors_in_movie aim where aim.actor_id = a.actor_id and movie_id=".$file_id);
      if ( !empty( $actor_list ) )
      {
        $actors_path = $xml->appendChild($movie_path,'<actors />');
        foreach ($actor_list as $actor)
        {
          $xpath = $xml->appendChild($actors_path,'<actor />');
          $xml->appendChild($xpath,'<name>'.utf8_encode(htmlspecialchars($actor["NAME"])).'</name>');
        }
      }

      // Certificates
      if ( !empty( $details[0]["CERTIFICATE"] ) )
      {
        $certificate = db_toarray("select name, scheme from certificates where cert_id = ".$details[0]["CERTIFICATE"]);
        $xpath = $xml->appendChild($movie_path,'<certificates />');
        $xml->appendChild($xpath,'<certificate scheme="'.$certificate[0]["SCHEME"].'">'.utf8_encode(htmlspecialchars($certificate[0]["NAME"])).'</certificate>');
      }

      // Genres
      $genre_list = db_toarray("select genre_name name from genres g, genres_of_movie gom where gom.genre_id = g.genre_id and movie_id=".$file_id);
      if ( !empty( $genre_list ) )
      {
        $xpath = $xml->appendChild($movie_path,'<genres />');
        foreach ($genre_list as $genre)
          $xml->appendChild($xpath,'<genre>'.utf8_encode(htmlspecialchars($genre["NAME"])).'</genre>');
      }

      // Directors
      $director_list = db_toarray("select director_name name from directors d, directors_of_movie dom where dom.director_id = d.director_id and movie_id=".$file_id);
      if ( !empty( $director_list ) )
      {
        $xpath = $xml->appendChild($movie_path,'<directors />');
        foreach ($director_list as $director)
          $xml->appendChild($xpath,'<director>'.utf8_encode(htmlspecialchars($director["NAME"])).'</director>');
      }

      // Running Time
      if ( !empty( $details[0]["LENGTH"] ) )
        $xml->appendChild($movie_path,'<runtime>'.floor($details[0]["LENGTH"]/60).'</runtime>');

      // Year
      if ( !empty( $details[0]["YEAR"] ) )
      {
        $xml->appendChild($movie_path,'<year>'.$details[0]["YEAR"].'</year>');
      }

      // Rating
      if ( !empty( $details[0]["EXTERNAL_RATING_PC"] ) )
      {
        $xml->appendChild($movie_path,'<rating>'.($details[0]["EXTERNAL_RATING_PC"]/10).'</rating>');
      }

      // Viewed Status
      $user_list = db_toarray("select name from users u, viewings v where u.user_id=v.user_id and v.media_type=".MEDIA_TYPE_VIDEO." and v.media_id = ".$file_id);
      if ( !empty ( $user_list ) )
      {
        $xpath = $xml->appendChild($movie_path,'<viewed />');
        foreach ($user_list as $user)
          $xml->appendChild($xpath,'<name>'.utf8_encode(htmlspecialchars($user["NAME"])).'</name>');
      }

      return $xml->exportToFile( $filename );
    }
  }

  function import_movie_from_xml ( $file_id, $filename )
  {
    $options = array(XML_OPTION_CASE_FOLDING => FALSE, XML_OPTION_SKIP_WHITE => TRUE);
    $xml = new XPath(FALSE, $options);

    $xml->importFromFile($filename);

    $data = $xml->match("/movie[1]/title");
    if ( !empty($data) ) $columns["TITLE"] = utf8_decode($xml->getData($data[0]));
    $data = $xml->match("/movie[1]/synopsis");
    if ( !empty($data) ) $columns["SYNOPSIS"] = utf8_decode($xml->getData($data[0]));
    $data = $xml->match("/movie[1]/year");
    if ( !empty($data) ) $columns["YEAR"] = $xml->getData($data[0]);
    $data = $xml->match("/movie[1]/rating");
    if ( !empty($data) ) $columns["EXTERNAL_RATING_PC"] = $xml->getData($data[0]) * 10;
    $columns["DETAILS_AVAILABLE"] = 'Y';
    scdb_set_movie_attribs($file_id, $columns);

    // Actors
    @db_sqlcommand('delete from actors_in_movie where movie_id = '.$file_id);
    $actors = $xml->match('/movie[1]/actors[1]/actor');
    if ( !empty($actors) )
    {
      $data = array();
      foreach ($actors as $actorpath)
        $data[] = utf8_decode($xml->getData($actorpath.'/name'));
      scdb_add_actors($file_id,$data);
    }

    // Certificates
    foreach ($xml->match('/movie[1]/certificates[1]/certificate[1]') as $certpath)
    {
      $list = $xml->getAttributes($certpath);
      $cert = $xml->getData($certpath) ;
      $scheme = $list["scheme"] ;
      $cert_id = db_value("select cert_id from certificates where scheme='$scheme' and name='$cert'");
      db_sqlcommand('update movies set certificate = '.$cert_id.' where file_id = '.$file_id);
    }

    // Genres
    @db_sqlcommand('delete from genres_of_movie where movie_id = '.$file_id);
    $genres  =$xml->match('/movie[1]/genres[1]/genre');
    if ( !empty($genres) )
    {
      $data = array();
      foreach ($genres as $genrepath)
        $data[] = utf8_decode($xml->getData($genrepath));
      scdb_add_genres($file_id,$data);
    }

    // Directors
    @db_sqlcommand('delete from directors_of_movie where movie_id = '.$file_id) ;
    $directors = $xml->match('/movie[1]/directors[1]/director');
    if ( !empty($directors) )
    {
      $data = array();
      foreach ($directors  as $directorpath)
        $data[] = utf8_decode($xml->getData($directorpath));
      scdb_add_directors($file_id,$data);
    }

    $viewed = $xml->match('/movie[1]/viewed[1]/name') ;
    if ( !empty($viewed) )
    {
      foreach ( $viewed as $viewedpath )
      {
        $name = utf8_decode($xml->getData($viewedpath));
        $data = db_value("SELECT user_id FROM users where name='".$name."'");
        @db_sqlcommand("insert into viewings (user_id, media_type, media_id, last_viewed, total_viewings ) values (".$data.", ".MEDIA_TYPE_VIDEO.", ".$file_id.", now(), 1) ");
      }
    }

    remove_orphaned_movie_info();

    return true;
  }

  /**
   * Exports the tv episode details to a file
   *
   */

  function export_tv_to_xml ( $file_id )
  {
    $details  = db_toarray("select * from tv where file_id = $file_id");
    $filename = substr($details[0]["DIRNAME"].$details[0]["FILENAME"],0,strrpos($details[0]["DIRNAME"].$details[0]["FILENAME"],'.')).".xml";

    if ( empty($details) )
    {
      send_to_log(5, "Unable to obtain tv details for XML export for file_id=$file_id");
      return false;
    }
    elseif ( !is_writable(dirname($filename)) )
    {
      send_to_log(4, "Unable to write tv XML file: $filename");
      return false;
    }
    else
    {
      send_to_log(5, 'Saving tv XML:', $filename);
      $options = array(XML_OPTION_CASE_FOLDING => FALSE, XML_OPTION_SKIP_WHITE => TRUE);
      $xml = new XPath(FALSE, $options);
      $xml->importFromString('<?xml version="1.0" encoding="utf-8"?><tv xmlns="http://www.swisscenter.co.uk" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="http://www.swisscenter.co.uk tv.xsd"></tv>');
      $tv_path = '/tv[1]';

      $xml->appendChild($tv_path,'<programme>'.utf8_encode(htmlspecialchars($details[0]["PROGRAMME"])).'</programme>');
      $xml->appendChild($tv_path,'<series>'.$details[0]["SERIES"].'</series>');
      $xml->appendChild($tv_path,'<episode>'.$details[0]["EPISODE"].'</episode>');
      $xml->appendChild($tv_path,'<title>'.utf8_encode(htmlspecialchars($details[0]["TITLE"])).'</title>');
      if ( !empty( $details[0]["SYNOPSIS"] ) )
        $xml->appendChild($tv_path,'<synopsis>'.utf8_encode(htmlspecialchars($details[0]["SYNOPSIS"])).'</synopsis>');

      // Actors
      $actor_list = db_toarray("select actor_name name from actors a, actors_in_tv ait where ait.actor_id = a.actor_id and tv_id=".$file_id);
      if ( !empty( $actor_list ) )
      {
        $actors_path = $xml->appendChild($tv_path,'<actors />');
        foreach ($actor_list as $actor)
        {
          $xpath = $xml->appendChild($actors_path,'<actor />');
          $xml->appendChild($xpath,'<name>'.utf8_encode(htmlspecialchars($actor["NAME"])).'</name>');
        }
      }

      // Certificates
      if ( !empty( $details[0]["CERTIFICATE"] ) )
      {
        $certificate = db_toarray("select name, scheme from certificates where cert_id = ".$details[0]["CERTIFICATE"]);
        $xpath = $xml->appendChild($tv_path,'<certificates />');
        $xml->appendChild($xpath,'<certificate scheme="'.$certificate[0]["SCHEME"].'">'.utf8_encode(htmlspecialchars($certificate[0]["NAME"])).'</certificate>');
      }

      // Genres
      $genre_list = db_toarray("select genre_name name from genres g, genres_of_tv got where got.genre_id = g.genre_id and tv_id=".$file_id);
      if ( !empty( $genre_list ) )
      {
        $xpath = $xml->appendChild($tv_path,'<genres />');
        foreach ($genre_list as $genre)
          $xml->appendChild($xpath,'<genre>'.utf8_encode(htmlspecialchars($genre["NAME"])).'</genre>');
      }

      // Directors
      $director_list = db_toarray("select director_name name from directors d, directors_of_tv dot where dot.director_id = d.director_id and tv_id=".$file_id);
      if ( !empty( $director_list ) )
      {
        $xpath = $xml->appendChild($tv_path,'<directors />');
        foreach ($director_list as $director)
          $xml->appendChild($xpath,'<director>'.utf8_encode(htmlspecialchars($director["NAME"])).'</director>');
      }

      // Running Time
      if ( !empty( $details[0]["LENGTH"] ) )
        $xml->appendChild($tv_path,'<runtime>'.floor($details[0]["LENGTH"]/60).'</runtime>');

      // Year
      if ( !empty( $details[0]["YEAR"] ) )
      {
        $xml->appendChild($tv_path,'<year>'.$details[0]["YEAR"].'</year>');
      }

      // Viewed Status
      $user_list = db_col_to_list("select name from users u, viewings v where u.user_id=v.user_id and v.media_type=".MEDIA_TYPE_TV." and v.media_id = ".$file_id);
      if ( !empty ( $user_list ) )
      {
        $xpath = $xml->appendChild($tv_path,'<viewed />');
        foreach ($user_list as $user)
          $xml->appendChild($xpath,'<name>'.utf8_encode(htmlspecialchars($user["NAME"])).'</name>');
      }

      return $xml->exportToFile( $filename );
    }
  }

  function import_tv_from_xml ( $file_id, $filename )
  {
    $options = array(XML_OPTION_CASE_FOLDING => FALSE, XML_OPTION_SKIP_WHITE => TRUE);
    $xml = new XPath(FALSE, $options);

    $xml->importFromFile($filename);

    $data = $xml->match("/tv[1]/programme");
    if ( !empty($data) ) $columns["PROGRAMME"] = utf8_decode($xml->getData($data[0]));
    $data = $xml->match("/tv[1]/series");
    if ( !empty($data) ) $columns["SERIES"] = $xml->getData($data[0]);
    $data = $xml->match("/tv[1]/episode");
    if ( !empty($data) ) $columns["EPISODE"] = $xml->getData($data[0]);
    $data = $xml->match("/tv[1]/title");
    if ( !empty($data) ) $columns["TITLE"] = utf8_decode($xml->getData($data[0]));
    $data = $xml->match("/tv[1]/synopsis");
    if ( !empty($data) ) $columns["SYNOPSIS"] = utf8_decode($xml->getData($data[0]));
    $data = $xml->match("/tv[1]/year");
    if ( !empty($data) ) $columns["YEAR"] = $xml->getData($data[0]);
    $columns["DETAILS_AVAILABLE"] = 'Y';
    scdb_set_tv_attribs($file_id, $columns);

    // Actors
    @db_sqlcommand('delete from actors_in_tv where tv_id = '.$file_id);
    $actors = $xml->match('/tv[1]/actors[1]/actor');
    if ( !empty($actors) )
    {
      $data = array();
      foreach ($actors as $actorpath)
        $data[] = utf8_decode($xml->getData($actorpath.'/name'));
      scdb_add_tv_actors($file_id,$data);
    }

    // Certificates
    foreach ($xml->match('/tv[1]/certificates[1]/certificate[1]') as $certpath)
    {
      $list = $xml->getAttributes($certpath);
      $cert = $xml->getData($certpath) ;
      $scheme = $list["scheme"] ;
      $cert_id = db_value("select cert_id from certificates where scheme='$scheme' and name='$cert'");
      db_sqlcommand('update tv set certificate = '.$cert_id.' where file_id = '.$file_id);
    }

    // Genres
    @db_sqlcommand('delete from genres_of_tv where tv_id = '.$file_id);
    $genres  =$xml->match('/tv[1]/genres[1]/genre');
    if ( !empty($genres) )
    {
      $data = array();
      foreach ($genres as $genrepath)
        $data[] = utf8_decode($xml->getData($genrepath));
      scdb_add_tv_genres($file_id,$data);
    }

    // Directors
    @db_sqlcommand('delete from directors_of_tv where tv_id = '.$file_id) ;
    $directors = $xml->match('/tv[1]/directors[1]/director');
    if ( !empty($directors) )
    {
      $data = array();
      foreach ($directors  as $directorpath)
        $data[] = utf8_decode($xml->getData($directorpath));
      scdb_add_tv_directors($file_id,$data);
    }

    $viewed = $xml->match('/tv[1]/viewed[1]/name') ;
    if ( !empty($viewed) )
    {
      foreach ( $viewed as $viewedpath )
      {
        $name = utf8_decode($xml->getData($viewedpath));
        $data = db_value("SELECT user_id FROM users where name='".$name."'");
        @db_sqlcommand("insert into viewings (user_id, media_type, media_id, last_viewed, total_viewings ) values (".$data.", ".MEDIA_TYPE_TV.", ".$file_id.", now(), 1) ");
      }
    }

    remove_orphaned_tv_info();

    return true;
  }
?>
