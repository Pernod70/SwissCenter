<?
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor
 *************************************************************************************************/

  require_once("base/page.php");
  require_once("base/mysql.php");
  require_once("base/utils.php");
  require_once("base/file.php");

  // ----------------------------------------------------------------------------------------
  // Removes common parts of filenames that we don't want to search for...
  // (eg: file extension, file suffix ("CD1",etc) and non-alphanumeric chars.
  // ----------------------------------------------------------------------------------------
  
  function strip_title ($title)
  {
    $search  = array ( '/\.[^.]*$/'
                     , '/[^0-9A-Z-a-z() ]+/'
                     , '/\(.*\)/'
                     , '/ CD.*/i'
                     , '/ +$/');
    
    $replace = array ( ''
                     , ' '
                     , ' '
                     , ' '
                     , '');
    
    return preg_replace($search, $replace, $title);
  }

  // ----------------------------------------------------------------------------------------
  // Returns all the hyperlinks is the given string
  // ----------------------------------------------------------------------------------------
  
  function get_urls ($string) 
  {
    preg_match_all ('/<a.*href="(view_dvd[^"]*)"[^>]*>(.*)<\/a>/i', $string, &$matches);
    
    for ($i = 0; $i<count($matches[2]); $i++)
      $matches[2][$i] = preg_replace('/<[^>]*>/','',$matches[2][$i]);

    return $matches;
  }
  
  // ----------------------------------------------------------------------------------------
  // Returns the text between two given strings
  // ----------------------------------------------------------------------------------------
  
  function get_detail_text( &$string, $startstr, $endstr)
  {
    $start  = strpos($string,$startstr);
    $end    = strpos($string,$endstr)-1;

    if ($start === false || $end === false)
    {
      return '';
    }
    else
    {
      $text  = strip_tags(substr($string,$start+strlen($startstr),$end-$start-strlen($startstr)));
  
      if (strpos($text,'>') === false)
        return ltrim(rtrim($text));
      else 
        return ltrim(rtrim(substr($text,strpos($text,'>')+1)));
    }
  }
  
  // ----------------------------------------------------------------------------------------
  // Gets the text that corresponds to the given detail
  // ----------------------------------------------------------------------------------------

  function get_attrib(&$text, $name)
  {
    preg_match('/'.$name.'\s{3,}(.*)\s{3,}/i',$text, $matches);
    $search  = array('/&bull;/','/&gt;/');
    $replace = array(',',':');
    
    return preg_replace($search, $replace, $matches[1]);
  }

  // ----------------------------------------------------------------------------------------
  // Updates the actors list, and assigns them to the given movie
  // ----------------------------------------------------------------------------------------

  function db_set_actors ( $movie_id, $actor_list )
  {
    $array = explode(',',$actor_list);
    foreach ($array as $actor)
    {
      $actor = rtrim(ltrim($actor));
      // Insert the actor into the table (we don't care if this violates an unique constraint)
      if (! empty($actor))
      {
        db_sqlcommand("insert into actors values (0,'$actor')");
        $actor_id = db_value("select actor_id from actors where actor_name='$actor'");
        db_sqlcommand("insert into actors_in_movie values ($movie_id, $actor_id)");
      }
    }
  }

  // ----------------------------------------------------------------------------------------
  // Updates the directors list, and assigns them to the given movie
  // ----------------------------------------------------------------------------------------

  function db_set_directors ( $movie_id, $dir_list )
  {
    $array = explode(',',$dir_list);
    foreach ($array as $dir)
    {
      $dir = rtrim(ltrim($dir));
      // Insert the director into the table (we don't care if this violates an unique constraint)
      if (! empty($actor))
      {
        db_sqlcommand("insert into directors values (0,'$dir')");
        $dir_id = db_value("select director_id from directors where director_name='$dir'");
        db_sqlcommand("insert into directors_of_movie values ($movie_id, $dir_id)");
      }
    }
  }

  // ----------------------------------------------------------------------------------------
  // Updates the genre list, and assigns them to the given movie
  // ----------------------------------------------------------------------------------------

  function db_set_genres ( $movie_id, $genre_list )
  {
    $array = explode(',',$genre_list);
    foreach ($array as $genre)
    {
      $genre = rtrim(ltrim($genre));
      // Insert the genre into the table (we don't care if this violates an unique constraint)
      if (! empty($genre))
      {
        db_sqlcommand("insert into genres values (0,'$genre')");
        $genre_id = db_value("select genre_id from genres where genre_name='$genre'");
        db_sqlcommand("insert into genres_of_movie values ($movie_id, $genre_id)");
      }
    }
  }

  // ----------------------------------------------------------------------------------------
  // Updates the MOVIE row in the database
  // ----------------------------------------------------------------------------------------

  function db_set_attribs ( $movie_id, $year, $cert)
  {
    db_sqlcommand("update movies set year='$year', rating='$cert', details_available='Y' where file_id=$movie_id");
  }  
  
  //===========================================================================================
  // Main script logic
  //===========================================================================================

  set_time_limit(86400);
  $site_url    = 'http://www.lovefilm.com/';
  $search_url  = $site_url.'search.php?searchtype=title&dvdsearch=';
  send_to_log('Checking online for extra movie information');

  // Obtain a list of movies that have not been checked online
//  $data = db_toarray("select file_id, filename from movies where details_available is null");
  $data = db_toarray("select file_id, filename from movies");

  // Process each movie
  foreach ($data as $row)
  {
    $file_id = $row["FILE_ID"];
    $film_title = strip_title($row["FILENAME"]);
    $html    = file_get_contents($search_url.str_replace(' ','+',$film_title));
    $matches = get_urls($html);

    send_to_log('Checking movie file : '.$row["FILENAME"]);
    send_to_log('Matching to : '.$film_title);
        
    if (strpos($html,"Search Results") !== false)
    { 
      // We were presented with a page of possible results
      // assume the first hit is correct for now!
      $html = file_get_contents($site_url.$matches[1][0]);
      send_to_log('Multiple Matches found, assuming first',$matches[2]);
    }

    // Determine attributes for the movie and update the database
    $details = get_detail_text($html,"Recommend DVD","MEMBER RATINGS");   
    db_set_directors ($file_id, get_attrib($details,"Director:"));
    db_set_actors    ($file_id, get_attrib($details,"Starring:"));
    db_set_genres    ($file_id, get_attrib($details,"Genre\(s\):"));    
    db_set_attribs   ($file_id, get_attrib($details,"Year:") ,get_attrib($details,"Certificate:"));
  }
      
  // Finished!
  send_to_log('Online movie check complete');
      

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
