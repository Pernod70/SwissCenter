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
    elseif ( !is_writable(dirname($filename)) )
    {
      send_to_log(4, "Unable to write video XML file: $filename");
      return false;
    }
    else
    {
      send_to_log(5, 'Saving movie XML:', $filename);

      $xml = new XmlBuilder();
      $xml->Push('movie', array('xmlns'=>'http://www.swisscenter.co.uk', 'xmlns:xsi'=>'http://www.w3.org/2001/XMLSchema-instance', 'xsi:schemaLocation'=>'http://www.swisscenter.co.uk movies.xsd'));
      $xml->Element('title', utf8_encode($details[0]["TITLE"]));
      if ( !empty( $details[0]["SYNOPSIS"] ) )
        $xml->Element('synopsis', utf8_encode($details[0]["SYNOPSIS"]));

      // Actors
      $actor_list = db_toarray("select actor_name name from actors a, actors_in_movie aim where aim.actor_id = a.actor_id and movie_id=".$file_id);
      if ( !empty( $actor_list ) )
      {
        $xml->Push('actors');
        foreach ($actor_list as $actor)
        {
          $xml->Push('actor');
          $xml->Element('name', utf8_encode($actor["NAME"]));
          $xml->Pop('actor');
        }
        $xml->Pop('actors');
      }

      // Certificates
      if ( !empty( $details[0]["CERTIFICATE"] ) )
      {
        $certificate = db_toarray("select name, scheme from certificates where cert_id = ".$details[0]["CERTIFICATE"]);
        $xml->Push('certificates');
        $xml->Element('certificate', utf8_encode($certificate[0]["NAME"]), array('scheme'=>$certificate[0]["SCHEME"]));
        $xml->Pop('certificates');
      }

      // Genres
      $genre_list = db_toarray("select genre_name name from genres g, genres_of_movie gom where gom.genre_id = g.genre_id and movie_id=".$file_id);
      if ( !empty( $genre_list ) )
      {
        $xml->Push('genres');
        foreach ($genre_list as $genre)
          $xml->Element('genre', utf8_encode($genre["NAME"]));
        $xml->Pop('genres');
      }

      // Directors
      $director_list = db_toarray("select director_name name from directors d, directors_of_movie dom where dom.director_id = d.director_id and movie_id=".$file_id);
      if ( !empty( $director_list ) )
      {
        $xml->Push('directors');
        foreach ($director_list as $director)
          $xml->Element('director', utf8_encode($director["NAME"]));
        $xml->Pop('directors');
      }

      // Languages
      $language_list = db_toarray("select language name from languages l, languages_of_movie lom where lom.language_id = l.language_id and movie_id=".$file_id);
      if ( !empty( $language_list ) )
      {
        $xml->Push('languages');
        foreach ($language_list as $language)
          $xml->Element('language', utf8_encode($language["NAME"]));
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
      $user_list = db_toarray("select distinct(name) from users u, viewings v where u.user_id=v.user_id and v.media_type=".MEDIA_TYPE_VIDEO." and v.media_id = ".$file_id);
      if ( !empty ( $user_list ) )
      {
        $xml->Push('viewed');
        foreach ($user_list as $user)
          $xml->Element('name', utf8_encode($user["NAME"]));
        $xml->Pop('viewed');
      }

      $xml->Pop('movie');
      if ($fsp = fopen($filename, 'wb'))
      {
        fwrite($fsp, $xml->getXml());
        fclose($fsp);
      }
    }
  }

  function import_movie_from_xml ( $file_id, $filename )
  {
    $sidecar = file_get_contents($filename);
    $xml = new XmlParser($sidecar, array(XML_OPTION_CASE_FOLDING => FALSE, XML_OPTION_SKIP_WHITE => TRUE) );
    $movie = $xml->GetData();

    if ( isset($movie['movie']['title']['VALUE']) )    $columns["TITLE"] = utf8_decode(xmlspecialchars_decode($movie['movie']['title']['VALUE']));
    if ( isset($movie['movie']['synopsis']['VALUE']) ) $columns["SYNOPSIS"] = utf8_decode(xmlspecialchars_decode($movie['movie']['synopsis']['VALUE']));
    if ( isset($movie['movie']['year']['VALUE']) )     $columns["YEAR"] = $movie['movie']['year']['VALUE'];
    if ( isset($movie['movie']['rating']['VALUE']) )   $columns["EXTERNAL_RATING_PC"] = $movie['movie']['rating']['VALUE'] * 10;
    if ( isset($movie['movie']['trailer']['VALUE']) )  $columns["TRAILER"] = xmlspecialchars_decode($movie['movie']['trailer']['VALUE']);
    $columns["DETAILS_AVAILABLE"] = 'Y';
    scdb_set_movie_attribs($file_id, $columns);

    // Actors
    @db_sqlcommand('delete from actors_in_movie where movie_id = '.$file_id);
    if ( isset($movie['movie']['actors']['actor']) )
    {
      if ( !isset($movie['movie']['actors']['actor'][0]) )
        $movie['movie']['actors']['actor'] = array($movie['movie']['actors']['actor']);
      $data = array();
      foreach ($movie['movie']['actors']['actor'] as $actor)
        $data[] = utf8_decode(xmlspecialchars_decode($actor['name']['VALUE']));
      scdb_add_actors($file_id,$data);
    }

    // Certificates
    if ( isset($movie['movie']['certificates']) )
    {
      $cert = $movie['movie']['certificates']['certificate'];
      $cert_id = db_value("select cert_id from certificates where scheme='".$cert['scheme']."' and name='".$cert['VALUE']."'");
      db_sqlcommand('update movies set certificate = '.$cert_id.' where file_id = '.$file_id);
    }

    // Genres
    @db_sqlcommand('delete from genres_of_movie where movie_id = '.$file_id);
    if ( isset($movie['movie']['genres']['genre']) )
    {
      if ( !isset($movie['movie']['genres']['genre'][0]) )
        $movie['movie']['genres']['genre'] = array($movie['movie']['genres']['genre']);
      $data = array();
      foreach ($movie['movie']['genres']['genre'] as $genre)
        $data[] = utf8_decode(xmlspecialchars_decode($genre['VALUE']));
      scdb_add_genres($file_id,$data);
    }

    // Directors
    @db_sqlcommand('delete from directors_of_movie where movie_id = '.$file_id) ;
    if ( isset($movie['movie']['directors']['director']) )
    {
      if ( !isset($movie['movie']['directors']['director'][0]) )
        $movie['movie']['directors']['director'] = array($movie['movie']['directors']['director']);
      $data = array();
      foreach ($movie['movie']['directors']['director'] as $director)
        $data[] = utf8_decode(xmlspecialchars_decode($director['VALUE']));
      scdb_add_directors($file_id,$data);
    }

    // Languages
    @db_sqlcommand('delete from languages_of_movie where movie_id = '.$file_id) ;
    if ( isset($movie['movie']['languages']['language']) )
    {
      if ( !isset($movie['movie']['languages']['language'][0]) )
        $movie['movie']['languages']['language'] = array($movie['movie']['languages']['language']);
      $data = array();
      foreach ($movie['movie']['languages']['language'] as $language)
        $data[] = utf8_decode(xmlspecialchars_decode($language['VALUE']));
      scdb_add_languages($file_id,$data);
    }

    // Viewed Status
    if ( isset($movie['movie']['viewed']) )
    {
      if ( !isset($movie['movie']['viewed']['name'][0]) )
        $movie['movie']['viewed']['name'] = array($movie['movie']['viewed']['name']);
      foreach ( $movie['movie']['viewed']['name'] as $viewed )
      {
        $name = utf8_decode(xmlspecialchars_decode($viewed['VALUE']));
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
    elseif ( !is_writable(dirname($filename)) )
    {
      send_to_log(4, "Unable to write tv XML file: $filename");
      return false;
    }
    else
    {
      send_to_log(5, 'Saving tv XML:', $filename);

      $xml = new XmlBuilder();
      $xml->Push('tv', array('xmlns'=>'http://www.swisscenter.co.uk', 'xmlns:xsi'=>'http://www.w3.org/2001/XMLSchema-instance', 'xsi:schemaLocation'=>'http://www.swisscenter.co.uk tv.xsd'));
      $xml->Element('programme', utf8_encode($details[0]["PROGRAMME"]));
      $xml->Element('series', $details[0]["SERIES"]);
      $xml->Element('episode', $details[0]["EPISODE"]);
      $xml->Element('title', utf8_encode($details[0]["TITLE"]));
      if ( !empty( $details[0]["SYNOPSIS"] ) )
        $xml->Element('synopsis', utf8_encode($details[0]["SYNOPSIS"]));

      // Actors
      $actor_list = db_toarray("select actor_name name from actors a, actors_in_tv ait where ait.actor_id = a.actor_id and tv_id=".$file_id);
      if ( !empty( $actor_list ) )
      {
        $xml->Push('actors');
        foreach ($actor_list as $actor)
        {
          $xml->Push('actor');
          $xml->Element('name', utf8_encode($actor["NAME"]));
          $xml->Pop('actor');
        }
        $xml->Pop('actors');
      }

      // Certificates
      if ( !empty( $details[0]["CERTIFICATE"] ) )
      {
        $certificate = db_toarray("select name, scheme from certificates where cert_id = ".$details[0]["CERTIFICATE"]);
        $xml->Push('certificates');
        $xml->Element('certificate', utf8_encode($certificate[0]["NAME"]), array('scheme'=>$certificate[0]["SCHEME"]));
        $xml->Pop('certificates');
      }

      // Genres
      $genre_list = db_toarray("select genre_name name from genres g, genres_of_tv got where got.genre_id = g.genre_id and tv_id=".$file_id);
      if ( !empty( $genre_list ) )
      {
        $xml->Push('genres');
        foreach ($genre_list as $genre)
          $xml->Element('genre', utf8_encode($genre["NAME"]));
        $xml->Pop('genres');
      }

      // Directors
      $director_list = db_toarray("select director_name name from directors d, directors_of_tv dot where dot.director_id = d.director_id and tv_id=".$file_id);
      if ( !empty( $director_list ) )
      {
        $xml->Push('directors');
        foreach ($director_list as $director)
          $xml->Element('director', utf8_encode($director["NAME"]));
        $xml->Pop('directors');
      }

      // Languages
      $language_list = db_toarray("select language name from languages l, languages_of_tv lot where lot.language_id = l.language_id and tv_id=".$file_id);
      if ( !empty( $language_list ) )
      {
        $xml->Push('languages');
        foreach ($language_list as $language)
          $xml->Element('language', utf8_encode($language["NAME"]));
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
      $user_list = db_toarray("select distinct(name) from users u, viewings v where u.user_id=v.user_id and v.media_type=".MEDIA_TYPE_TV." and v.media_id = ".$file_id);
      if ( !empty ( $user_list ) )
      {
        $xml->Push('viewed');
        foreach ($user_list as $user)
          $xml->Element('name', utf8_encode($user["NAME"]));
        $xml->Pop('viewed');
      }

      $xml->Pop('tv');
      if ($fsp = fopen($filename, 'wb'))
      {
        fwrite($fsp, $xml->getXml());
        fclose($fsp);
      }
    }
  }

  function import_tv_from_xml ( $file_id, $filename )
  {
    $sidecar = file_get_contents($filename);
    $xml = new XmlParser($sidecar, array(XML_OPTION_CASE_FOLDING => FALSE, XML_OPTION_SKIP_WHITE => TRUE) );
    $tv = $xml->GetData();

    if ( isset($tv['tv']['programme']['VALUE']) ) $columns["PROGRAMME"] = utf8_decode(xmlspecialchars_decode($tv['tv']['programme']['VALUE']));
    if ( isset($tv['tv']['series']['VALUE']) )    $columns["SERIES"] = $tv['tv']['series']['VALUE'];
    if ( isset($tv['tv']['episode']['VALUE']) )   $columns["EPISODE"] = $tv['tv']['episode']['VALUE'];
    if ( isset($tv['tv']['title']['VALUE']) )     $columns["TITLE"] = utf8_decode(xmlspecialchars_decode($tv['tv']['title']['VALUE']));
    if ( isset($tv['tv']['synopsis']['VALUE']) )  $columns["SYNOPSIS"] = utf8_decode(xmlspecialchars_decode($tv['tv']['synopsis']['VALUE']));
    if ( isset($tv['tv']['year']['VALUE']) )      $columns["YEAR"] = $tv['tv']['year']['VALUE'];
    if ( isset($tv['tv']['rating']['VALUE']) )    $columns["EXTERNAL_RATING_PC"] = $tv['tv']['rating']['VALUE'] * 10;
    $columns["DETAILS_AVAILABLE"] = 'Y';
    scdb_set_tv_attribs($file_id, $columns);

    // Actors
    @db_sqlcommand('delete from actors_in_tv where tv_id = '.$file_id);
    if ( isset($tv['tv']['actors']['actor']) )
    {
      if ( !isset($tv['tv']['actors']['actor'][0]) )
        $tv['tv']['actors']['actor'] = array($tv['tv']['actors']['actor']);
      $data = array();
      foreach ($tv['tv']['actors']['actor'] as $actor)
        $data[] = utf8_decode(xmlspecialchars_decode($actor['name']['VALUE']));
      scdb_add_tv_actors($file_id,$data);
    }

    // Certificates
    if ( isset($tv['tv']['certificates']) )
    {
      $cert = $tv['tv']['certificates']['certificate'];
      $cert_id = db_value("select cert_id from certificates where scheme='".$cert['scheme']." and name='".$cert['VALUE']."'");
      db_sqlcommand('update tv set certificate = '.$cert_id.' where file_id = '.$file_id);
    }

    // Genres
    @db_sqlcommand('delete from genres_of_tv where tv_id = '.$file_id);
    if ( isset($tv['tv']['genres']['genre']) )
    {
      if ( !isset($tv['tv']['genres']['genre'][0]) )
        $tv['tv']['genres']['genre'] = array($tv['tv']['genres']['genre']);
      $data = array();
      foreach ($tv['tv']['genres']['genre'] as $genre)
        $data[] = utf8_decode(xmlspecialchars_decode($genre['VALUE']));
      scdb_add_tv_genres($file_id,$data);
    }

    // Directors
    @db_sqlcommand('delete from directors_of_tv where tv_id = '.$file_id) ;
    if ( isset($tv['tv']['directors']['director']) )
    {
      if ( !isset($tv['tv']['directors']['director'][0]) )
        $tv['tv']['directors']['director'] = array($tv['tv']['directors']['director']);
      $data = array();
      foreach ($tv['tv']['directors']['director'] as $director)
        $data[] = utf8_decode(xmlspecialchars_decode($director['VALUE']));
      scdb_add_tv_directors($file_id,$data);
    }

    // Languages
    @db_sqlcommand('delete from languages_of_tv where tv_id = '.$file_id) ;
    if ( isset($tv['tv']['languages']['language']) )
    {
      if ( !isset($tv['tv']['languages']['language'][0]) )
        $tv['tv']['languages']['language'] = array($tv['tv']['languages']['language']);
      $data = array();
      foreach ($tv['tv']['languages']['language'] as $language)
        $data[] = utf8_decode(xmlspecialchars_decode($language['VALUE']));
      scdb_add_tv_languages($file_id,$data);
    }

    // Viewed Status
    if ( isset($tv['tv']['viewed']) )
    {
      if ( !isset($tv['tv']['viewed']['name'][0]) )
        $tv['tv']['viewed']['name'] = array($tv['tv']['viewed']['name']);
      foreach ( $tv['tv']['viewed']['name'] as $viewed )
      {
        $name = utf8_decode($viewed['VALUE']);
        $user_id = db_value("SELECT user_id FROM users where name='".$name."'");
        if (viewings_count(MEDIA_TYPE_TV, $file_id, $user_id) == 0)
          db_insert_row('viewings',array("user_id"=>$user_id, "media_type"=>MEDIA_TYPE_TV, "media_id"=>$file_id, "total_viewings"=>1));
      }
    }

    remove_orphaned_tv_info();

    return true;
  }
?>
