<?
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor
 *************************************************************************************************/

  require_once( realpath(dirname(__FILE__).'/../../base/mysql.php'));
  require_once( realpath(dirname(__FILE__).'/../../base/file.php'));
  require_once( realpath(dirname(__FILE__).'/xmlbuilder.php'));

  function export_movie_to_xml ( $file_id, $filename )
  {
  	$movie = db_toarray("select * from movies where file_id = ".$file_id);

  	if ( empty($movie) )
  	{
  	  send_to_log(5,"Unable to obtain movie information (FILE_ID=$file_id)");
  	  return false;
  	}
  	else
  	{
      $xml = new XmlBuilder();
      $xml->push('Movies', array( "xmlns"              => "http://www.swisscenter.co.uk"
                                , "xmlns:xsi"          => "http://www.w3.org/2001/XMLSchema-instance"
                                , "xsi:schemaLocation" => "http://www.swisscenter.co.uk movies.xsd"
                                ));                              
                                    
      $movie = array_pop($movie);      
  	  $runtime = floor($movie["LENGTH"]/60);      

  	  $xml->push('movie');
      $xml->element('title',$movie["TITLE"]);
      $xml->element('year',$movie["YEAR"]);
      $xml->element('synopsis',$movie["SYNOPSIS"]);
      $xml->element('language',"");          // TO-DO
      $xml->element('tagline',"");           // TO-DO
  
      if ($runtime > 0)
        $xml->element('runtime',$runtime);
  
      // Certificates
      $xml->push('certificates');
      $certificate = db_toarray("select name , scheme from certificates where cert_id = $movie[CERTIFICATE] ");
      $xml->element("certificate",$certificate[0]["NAME"], array("scheme"=>$certificate[0]["SCHEME"]));
      $xml->pop(); // </certificates>
  
      // Genres
      $xml->push('genres');
      $genre_list = db_toarray("select g.genre_name from genres g, genres_of_movie gom where gom.genre_id = g.genre_id and movie_id = $file_id ");
      foreach ($genre_list as $genre)
        $xml->element("genre",$genre["GENRE_NAME"]);
      $xml->pop(); // </genres>

      // Directors
      $xml->push('directors');
      $director_list = db_toarray("select d.director_name from directors d, directors_of_movie dom where dom.director_id = d.director_idand movie_id = $file_id ");
      foreach ($director_list as $director)
        $xml->element("director",$director["DIRECTOR_NAME"]);
      $xml->pop(); // </directors>

      // Actors 
      $actor_list = db_toarray("select a.actor_id, a.actor_name from actors a, actors_in_movie aim where aim.actor_id = a.actor_id and movie_id = $file_id ");
      $xml->push('actors');
      foreach ($actor_list as $actor)
      {
        $xml->push('actor');
        $xml->element('name',$actor["ACTOR_NAME"]);        

        // Characters
        $character_list = db_toarray("select character_name from actors_in_movie where character_name is not null and movie_id $file_id and actor_id = $actor[ACTOR_ID]" );
        if ( count($character_list) == 1)
        {
          $xml->element('character', $character["CHARACTER_NAME"]);
        }
        elseif ( coutn($character_list) > 1)
        {
          $xml->push('characters');
          foreach ($character_list as $character)
            $xml->element('character', $character["CHARACTER_NAME"]);
          $xml->pop(); // </characters>
        }
        $xml->pop(); // </actor>
      }
      $xml->pop(); // </actors>      
    	$xml->pop(); // </movie>

    	if ($fp = fopen($filename, 'w'))
      {
        @fwrite($fp , $xml->getXml());
        fclose($fp);
        return true;
      }   
      else 
      {
        send_to_log(5,'Unable to write movie information file to disk');
        return false;
      }        
    }
  }
?>