<?php
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor
 *************************************************************************************************/

  require_once( realpath(dirname(__FILE__).'/mysql.php'));
  require_once( realpath(dirname(__FILE__).'/../ext/xml/xmlparser.php'));
  require_once( realpath(dirname(__FILE__).'/../ext/xml/xmlbuilder.php'));

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
    elseif ( !Fsw::is_writable(dirname($filename)) )
    {
      send_to_log(4, "Unable to write video XML file: $filename");
      return false;
    }
    else
    {
      send_to_log(5, 'Saving movie XML:', $filename);

      $xml = new XmlBuilder();
      $xml->Push('movie', array('xmlns'=>'http://www.swisscenter.co.uk', 'xmlns:xsi'=>'http://www.w3.org/2001/XMLSchema-instance', 'xsi:schemaLocation'=>'http://www.swisscenter.co.uk movies.xsd'));

      // Title
      $xml->Element('title', $details[0]["TITLE"]);

      // IMDb
      if ( !empty( $details[0]["IMDB_ID"] ) )
        $xml->Element('imdb_id', $details[0]["IMDB_ID"]);

      // Synopsis
      if ( !empty( $details[0]["SYNOPSIS"] ) )
        $xml->Element('synopsis', $details[0]["SYNOPSIS"]);

      // Actors
      $actor_list = db_toarray("select actor_name name from actors a, actors_in_movie aim where aim.actor_id = a.actor_id and movie_id=".$file_id);
      if ( !empty( $actor_list ) )
      {
        $xml->Push('actors');
        foreach ($actor_list as $actor)
        {
          $xml->Push('actor');
          $xml->Element('name', $actor["NAME"]);
          $xml->Pop('actor');
        }
        $xml->Pop('actors');
      }

      // Certificates
      if ( !empty( $details[0]["CERTIFICATE"] ) )
      {
        $certificate = db_toarray("select name, scheme from certificates where cert_id = ".$details[0]["CERTIFICATE"]);
        $xml->Push('certificates');
        $xml->Element('certificate', $certificate[0]["NAME"], array('scheme'=>$certificate[0]["SCHEME"]));
        $xml->Pop('certificates');
      }

      // Genres
      $genre_list = db_toarray("select genre_name name from genres g, genres_of_movie gom where gom.genre_id = g.genre_id and movie_id=".$file_id);
      if ( !empty( $genre_list ) )
      {
        $xml->Push('genres');
        foreach ($genre_list as $genre)
          $xml->Element('genre', $genre["NAME"]);
        $xml->Pop('genres');
      }

      // Directors
      $director_list = db_toarray("select director_name name from directors d, directors_of_movie dom where dom.director_id = d.director_id and movie_id=".$file_id);
      if ( !empty( $director_list ) )
      {
        $xml->Push('directors');
        foreach ($director_list as $director)
          $xml->Element('director', $director["NAME"]);
        $xml->Pop('directors');
      }

      // Languages
      $language_list = db_toarray("select language name from languages l, languages_of_movie lom where lom.language_id = l.language_id and movie_id=".$file_id);
      if ( !empty( $language_list ) )
      {
        $xml->Push('languages');
        foreach ($language_list as $language)
          $xml->Element('language', $language["NAME"]);
        $xml->Pop('languages');
      }

      // Running Time
      if ( !empty( $details[0]["LENGTH"] ) )
        $xml->Element('runtime', floor($details[0]["LENGTH"]/60));

      // Year
      if ( !empty( $details[0]["YEAR"] ) )
        $xml->Element('year', $details[0]["YEAR"]);

      // Rating
      if ( !empty( $details[0]["EXTERNAL_RATING_PC"] ) )
        $xml->Element('rating', ($details[0]["EXTERNAL_RATING_PC"]/10));

      // Trailer
      if ( !empty( $details[0]["TRAILER"] ) )
        $xml->Element('trailer', $details[0]["TRAILER"]);

      // Viewed Status
      $user_list = db_toarray("select u.name, max(v.last_viewed) last_viewed, sum(v.total_viewings) total_viewings from users u, viewings v where u.user_id=v.user_id and v.media_type=".MEDIA_TYPE_VIDEO." and v.media_id = ".$file_id." group by u.name");
      if ( !empty ( $user_list ) )
      {
        $xml->Push('viewed');
        foreach ($user_list as $user)
          $xml->Element('name', $user["NAME"], array('viewings'=>$user["TOTAL_VIEWINGS"], 'last_viewed'=>$user["LAST_VIEWED"]));
        $xml->Pop('viewed');
      }

      $xml->Pop('movie');
      $fsp = Fsw::fopen($filename, 'wb');
      if ($fsp)
      {
        fwrite($fsp, $xml->getXml());
        fclose($fsp);
      }
    }
  }

  function import_movie_from_xml ( $file_id, $filename )
  {
    $sidecar = Fsw::file_get_contents($filename);
    $xml = new XmlParser($sidecar, array(XML_OPTION_CASE_FOLDING => TRUE, XML_OPTION_SKIP_WHITE => TRUE) );
    $movie = $xml->GetData();

    if ( isset($movie['MOVIE']['TITLE']['VALUE']) )    $columns["TITLE"] = xmlspecialchars_decode($movie['MOVIE']['TITLE']['VALUE']);
    if ( isset($movie['MOVIE']['IMDB_ID']['VALUE']) )  $columns["IMDB_ID"] = $movie['MOVIE']['IMDB_ID']['VALUE'];
    if ( isset($movie['MOVIE']['SYNOPSIS']['VALUE']) ) $columns["SYNOPSIS"] = xmlspecialchars_decode($movie['MOVIE']['SYNOPSIS']['VALUE']);
    if ( isset($movie['MOVIE']['YEAR']['VALUE']) )     $columns["YEAR"] = $movie['MOVIE']['YEAR']['VALUE'];
    if ( isset($movie['MOVIE']['RATING']['VALUE']) )   $columns["EXTERNAL_RATING_PC"] = $movie['MOVIE']['RATING']['VALUE'] * 10;
    if ( isset($movie['MOVIE']['TRAILER']['VALUE']) )  $columns["TRAILER"] = xmlspecialchars_decode($movie['MOVIE']['TRAILER']['VALUE']);
    $columns["DETAILS_AVAILABLE"] = 'Y';
    scdb_set_movie_attribs($file_id, $columns);

    // Actors
    @db_sqlcommand('delete from actors_in_movie where movie_id = '.$file_id);
    if ( isset($movie['MOVIE']['ACTORS']['ACTOR']) )
    {
      if ( !isset($movie['MOVIE']['ACTORS']['ACTOR'][0]) )
        $movie['MOVIE']['ACTORS']['ACTOR'] = array($movie['MOVIE']['ACTORS']['ACTOR']);
      $data = array();
      foreach ($movie['MOVIE']['ACTORS']['ACTOR'] as $actor)
        $data[] = xmlspecialchars_decode($actor['NAME']['VALUE']);
      scdb_add_actors($file_id,$data);
    }

    // Certificates
    if ( isset($movie['MOVIE']['CERTIFICATES']) )
    {
      $cert = $movie['MOVIE']['CERTIFICATES']['CERTIFICATE'];
      $cert_id = db_value("select cert_id from certificates where scheme='".$cert['SCHEME']."' and name='".$cert['VALUE']."'");
      db_sqlcommand('update movies set certificate = '.$cert_id.' where file_id = '.$file_id);
    }

    // Genres
    @db_sqlcommand('delete from genres_of_movie where movie_id = '.$file_id);
    if ( isset($movie['MOVIE']['GENRES']['GENRE']) )
    {
      if ( !isset($movie['MOVIE']['GENRES']['GENRE'][0]) )
        $movie['MOVIE']['GENRES']['GENRE'] = array($movie['MOVIE']['GENRES']['GENRE']);
      $data = array();
      foreach ($movie['MOVIE']['GENRES']['GENRE'] as $genre)
        $data[] = xmlspecialchars_decode($genre['VALUE']);
      scdb_add_genres($file_id,$data);
    }

    // Directors
    @db_sqlcommand('delete from directors_of_movie where movie_id = '.$file_id) ;
    if ( isset($movie['MOVIE']['DIRECTORS']['DIRECTOR']) )
    {
      if ( !isset($movie['MOVIE']['DIRECTORS']['DIRECTOR'][0]) )
        $movie['MOVIE']['DIRECTORS']['DIRECTOR'] = array($movie['MOVIE']['DIRECTORS']['DIRECTOR']);
      $data = array();
      foreach ($movie['MOVIE']['DIRECTORS']['DIRECTOR'] as $director)
        $data[] = xmlspecialchars_decode($director['VALUE']);
      scdb_add_directors($file_id,$data);
    }

    // Languages
    @db_sqlcommand('delete from languages_of_movie where movie_id = '.$file_id) ;
    if ( isset($movie['MOVIE']['LANGUAGES']['LANGUAGE']) )
    {
      if ( !isset($movie['MOVIE']['LANGUAGES']['LANGUAGE'][0]) )
        $movie['MOVIE']['LANGUAGES']['LANGUAGE'] = array($movie['MOVIE']['LANGUAGES']['LANGUAGE']);
      $data = array();
      foreach ($movie['MOVIE']['LANGUAGES']['LANGUAGE'] as $language)
        $data[] = xmlspecialchars_decode($language['VALUE']);
      scdb_add_languages($file_id,$data);
    }

    // Viewed Status
    if ( isset($movie['MOVIE']['VIEWED']) )
    {
      if ( !isset($movie['MOVIE']['VIEWED']['NAME'][0]) )
        $movie['MOVIE']['VIEWED']['NAME'] = array($movie['MOVIE']['VIEWED']['NAME']);
      foreach ( $movie['MOVIE']['VIEWED']['NAME'] as $viewed )
      {
        $name = xmlspecialchars_decode($viewed['VALUE']);
        $user_id = db_value("SELECT user_id FROM users where name='".$name."'");
        if (viewings_count(MEDIA_TYPE_VIDEO, $file_id, $user_id) == 0)
          db_insert_row('viewings',array("user_id"=>$user_id, "media_type"=>MEDIA_TYPE_VIDEO, "media_id"=>$file_id, "total_viewings"=>1));
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
    elseif ( !Fsw::is_writable(dirname($filename)) )
    {
      send_to_log(4, "Unable to write tv XML file: $filename");
      return false;
    }
    else
    {
      send_to_log(5, 'Saving tv XML:', $filename);

      $xml = new XmlBuilder();
      $xml->Push('tv', array('xmlns'=>'http://www.swisscenter.co.uk', 'xmlns:xsi'=>'http://www.w3.org/2001/XMLSchema-instance', 'xsi:schemaLocation'=>'http://www.swisscenter.co.uk tv.xsd'));
      $xml->Element('programme', $details[0]["PROGRAMME"]);
      $xml->Element('series', $details[0]["SERIES"]);
      $xml->Element('episode', $details[0]["EPISODE"]);
      $xml->Element('title', $details[0]["TITLE"]);
      if ( !empty( $details[0]["SYNOPSIS"] ) )
        $xml->Element('synopsis', $details[0]["SYNOPSIS"]);

      // Actors
      $actor_list = db_toarray("select actor_name name from actors a, actors_in_tv ait where ait.actor_id = a.actor_id and tv_id=".$file_id);
      if ( !empty( $actor_list ) )
      {
        $xml->Push('actors');
        foreach ($actor_list as $actor)
        {
          $xml->Push('actor');
          $xml->Element('name', $actor["NAME"]);
          $xml->Pop('actor');
        }
        $xml->Pop('actors');
      }

      // Certificates
      if ( !empty( $details[0]["CERTIFICATE"] ) )
      {
        $certificate = db_toarray("select name, scheme from certificates where cert_id = ".$details[0]["CERTIFICATE"]);
        $xml->Push('certificates');
        $xml->Element('certificate', $certificate[0]["NAME"], array('scheme'=>$certificate[0]["SCHEME"]));
        $xml->Pop('certificates');
      }

      // Genres
      $genre_list = db_toarray("select genre_name name from genres g, genres_of_tv got where got.genre_id = g.genre_id and tv_id=".$file_id);
      if ( !empty( $genre_list ) )
      {
        $xml->Push('genres');
        foreach ($genre_list as $genre)
          $xml->Element('genre', $genre["NAME"]);
        $xml->Pop('genres');
      }

      // Directors
      $director_list = db_toarray("select director_name name from directors d, directors_of_tv dot where dot.director_id = d.director_id and tv_id=".$file_id);
      if ( !empty( $director_list ) )
      {
        $xml->Push('directors');
        foreach ($director_list as $director)
          $xml->Element('director', $director["NAME"]);
        $xml->Pop('directors');
      }

      // Languages
      $language_list = db_toarray("select language name from languages l, languages_of_tv lot where lot.language_id = l.language_id and tv_id=".$file_id);
      if ( !empty( $language_list ) )
      {
        $xml->Push('languages');
        foreach ($language_list as $language)
          $xml->Element('language', $language["NAME"]);
        $xml->Pop('languages');
      }

      // Running Time
      if ( !empty( $details[0]["LENGTH"] ) )
        $xml->Element('runtime', floor($details[0]["LENGTH"]/60));

      // Year
      if ( !empty( $details[0]["YEAR"] ) )
        $xml->Element('year', $details[0]["YEAR"]);

      // Rating
      if ( !empty( $details[0]["EXTERNAL_RATING_PC"] ) )
        $xml->Element('rating', ($details[0]["EXTERNAL_RATING_PC"]/10));

      // Viewed Status
      $user_list = db_toarray("select u.name, max(v.last_viewed) last_viewed, sum(v.total_viewings) total_viewings from users u, viewings v where u.user_id=v.user_id and v.media_type=".MEDIA_TYPE_TV." and v.media_id = ".$file_id." group by u.name");
      if ( !empty ( $user_list ) )
      {
        $xml->Push('viewed');
        foreach ($user_list as $user)
          $xml->Element('name', $user["NAME"], array('viewings'=>$user["TOTAL_VIEWINGS"], 'last_viewed'=>$user["LAST_VIEWED"]));
        $xml->Pop('viewed');
      }

      $xml->Pop('tv');
      $fsp = Fsw::fopen($filename, 'wb');
      if ($fsp)
      {
        fwrite($fsp, $xml->getXml());
        fclose($fsp);
      }
    }
  }

  function import_tv_from_xml ( $file_id, $filename )
  {
    $sidecar = Fsw::file_get_contents($filename);
    $xml = new XmlParser($sidecar, array(XML_OPTION_CASE_FOLDING => TRUE, XML_OPTION_SKIP_WHITE => TRUE) );
    $tv = $xml->GetData();

    if ( isset($tv['TV']['PROGRAMME']['VALUE']) ) $columns["PROGRAMME"] = xmlspecialchars_decode($tv['TV']['PROGRAMME']['VALUE']);
    if ( isset($tv['TV']['SERIES']['VALUE']) )    $columns["SERIES"] = $tv['TV']['SERIES']['VALUE'];
    if ( isset($tv['TV']['EPISODE']['VALUE']) )   $columns["EPISODE"] = $tv['TV']['EPISODE']['VALUE'];
    if ( isset($tv['TV']['TITLE']['VALUE']) )     $columns["TITLE"] = xmlspecialchars_decode($tv['TV']['TITLE']['VALUE']);
    if ( isset($tv['TV']['SYNOPSIS']['VALUE']) )  $columns["SYNOPSIS"] = xmlspecialchars_decode($tv['TV']['SYNOPSIS']['VALUE']);
    if ( isset($tv['TV']['YEAR']['VALUE']) )      $columns["YEAR"] = $tv['TV']['YEAR']['VALUE'];
    if ( isset($tv['TV']['RATING']['VALUE']) )    $columns["EXTERNAL_RATING_PC"] = $tv['TV']['RATING']['VALUE'] * 10;
    $columns["DETAILS_AVAILABLE"] = 'Y';
    scdb_set_tv_attribs($file_id, $columns);

    // Actors
    @db_sqlcommand('delete from actors_in_tv where tv_id = '.$file_id);
    if ( isset($tv['TV']['ACTORS']['ACTOR']) )
    {
      if ( !isset($tv['TV']['ACTORS']['ACTOR'][0]) )
        $tv['TV']['ACTORS']['ACTOR'] = array($tv['TV']['ACTORS']['ACTOR']);
      $data = array();
      foreach ($tv['TV']['ACTORS']['ACTOR'] as $actor)
        $data[] = xmlspecialchars_decode($actor['NAME']['VALUE']);
      scdb_add_tv_actors($file_id,$data);
    }

    // Certificates
    if ( isset($tv['TV']['CERTIFICATES']) )
    {
      $cert = $tv['TV']['CERTIFICATES']['CERTIFICATE'];
      $cert_id = db_value("select cert_id from certificates where scheme='".$cert['SCHEME']." and name='".$cert['VALUE']."'");
      db_sqlcommand('update tv set certificate = '.$cert_id.' where file_id = '.$file_id);
    }

    // Genres
    @db_sqlcommand('delete from genres_of_tv where tv_id = '.$file_id);
    if ( isset($tv['TV']['GENRES']['GENRE']) )
    {
      if ( !isset($tv['TV']['GENRES']['GENRE'][0]) )
        $tv['TV']['GENRES']['GENRE'] = array($tv['TV']['GENRES']['GENRE']);
      $data = array();
      foreach ($tv['TV']['GENRES']['GENRE'] as $genre)
        $data[] = xmlspecialchars_decode($genre['VALUE']);
      scdb_add_tv_genres($file_id,$data);
    }

    // Directors
    @db_sqlcommand('delete from directors_of_tv where tv_id = '.$file_id) ;
    if ( isset($tv['TV']['DIRECTORS']['DIRECTOR']) )
    {
      if ( !isset($tv['TV']['DIRECTORS']['DIRECTOR'][0]) )
        $tv['TV']['DIRECTORS']['DIRECTOR'] = array($tv['TV']['DIRECTORS']['DIRECTOR']);
      $data = array();
      foreach ($tv['TV']['DIRECTORS']['DIRECTOR'] as $director)
        $data[] = xmlspecialchars_decode($director['VALUE']);
      scdb_add_tv_directors($file_id,$data);
    }

    // Languages
    @db_sqlcommand('delete from languages_of_tv where tv_id = '.$file_id) ;
    if ( isset($tv['TV']['LANGUAGES']['LANGUAGE']) )
    {
      if ( !isset($tv['TV']['LANGUAGES']['LANGUAGE'][0]) )
        $tv['TV']['LANGUAGES']['LANGUAGE'] = array($tv['TV']['LANGUAGES']['LANGUAGE']);
      $data = array();
      foreach ($tv['TV']['LANGUAGES']['LANGUAGE'] as $language)
        $data[] = xmlspecialchars_decode($language['VALUE']);
      scdb_add_tv_languages($file_id,$data);
    }

    // Viewed Status
    if ( isset($tv['TV']['VIEWED']) )
    {
      if ( !isset($tv['TV']['VIEWED']['NAME'][0]) )
        $tv['TV']['VIEWED']['NAME'] = array($tv['TV']['VIEWED']['NAME']);
      foreach ( $tv['TV']['VIEWED']['NAME'] as $viewed )
      {
        $name = $viewed['VALUE'];
        $user_id = db_value("SELECT user_id FROM users where name='".$name."'");
        if (viewings_count(MEDIA_TYPE_TV, $file_id, $user_id) == 0)
          db_insert_row('viewings',array("user_id"=>$user_id, "media_type"=>MEDIA_TYPE_TV, "media_id"=>$file_id, "total_viewings"=>1));
      }
    }

    remove_orphaned_tv_info();

    return true;
  }
?>
