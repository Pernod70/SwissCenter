<?php
/**************************************************************************************************
   SWISScenter Source
   
   This is one of a selection of scripts all designed to obtain information from the internet
   relating to movies that the user has added to their database. It typically collects
   information such as genre, year of release, synopsis, directors and actors.
      
   NOTE: This parser for OFDb.de is _NOT_ an official part of SWISScenter, and is not supported by the
   SWISScenter developers. Nigel (Pernod)
   
   Version history:
   15-Oct-2007: v1.0:     First public release
   10-Jan-2008: v1.1:     Added image download, and removed 'more' from synopsis.
   11-Jan-2008: v1.2:     Fixed bug with search, and now gets full synopsis.

 *************************************************************************************************/

  /**
   * Searches the OFDb.de site for movie details
   *
   * @param integer $id
   * @param string $filename
   * @param string $title
   * @return bool
   */
  function extra_get_movie_details($id, $filename, $title)
  {
    // The site URL (may be used later)
    $site_url     = 'http://www.ofdb.de/';
    $search_url   = 'http://www.google.com/search?hl=de&q=&q=site%3Aofdb.de&q=#####';
    $search_title = str_replace(' ','+',$title);
    $details      = db_toarray("select dirname, filename from movies where file_id = $id");
    
    send_to_log(4,"Searching for details about ".$title." online at '$site_url'");
                            
    // Get page from ofdb.de using Google 'I'm Feeling Lucky'
    $url_load = str_replace('#####',$search_title,$search_url);
    $html     = file_get_contents( $url_load );

    // Determine URL of first returned item, then load the page
    $start        = strpos($html, $site_url);
    $end          = strpos($html,'"',$start+1);
    $ofdb_url     = substr($html,$start,$end-$start);
    $html         = file_get_contents( $ofdb_url );
    
    if ($html != false)
    {
      $title_found = substr_between_strings($html,'<title>','</title>');
      send_to_log(4,"Found details for '.$title_found'");  
      
      // Download and store Albumart if there is none present
      if ( file_albumart($details[0]["DIRNAME"].$details[0]["FILENAME"], false) == '')
      {
        $film_id = substr($ofdb_url,strrpos($ofdb_url,'=')+1);
        $matches = array ();
        $matches = get_images_from_html($html);
        for ($i = 0; $i<count($matches[1]); $i++)
        {
          if (strpos($matches[1][$i],$film_id.'.jpg') !== false)
          {
            $orig_ext = file_ext($matches[1][$i]);
            file_save_albumart( $site_url.$matches[1][$i] , $details[0]["DIRNAME"].file_noext($details[0]["FILENAME"]).'.'.$orig_ext , $title);
          }
        }
      } 
         
      // Year
      $start = strpos($html,"Erscheinungsjahr:");
      $end = strpos($html,"</tr>",$start+1);
      $html_genres = substr($html,$start,$end-$start);
      $matches = get_urls_from_html($html_genres,"Jahr");
      $year = $matches[2][0];

      // Director(s)
      $start = strpos($html,"Regie:");
      $end = strpos($html,"</tr>",$start+1);
      $html_directed = substr($html,$start,$end-$start);
      $matches = array ();
      $matches = get_urls_from_html($html_directed,"Name");
      $new_directors = $matches[2];
        
      // Actor(s)
      $start = strpos($html,"Darsteller:");
      $end = strpos($html,"</tr>",$start+1);
      $html_actors = substr($html,$start,$end-$start);
      $matches = array ();
      $matches = get_urls_from_html($html_actors,"Name");
      $new_actors = $matches[2];
      
      // Genre
      $start = strpos($html,"Genre(s):");
      $end = strpos($html,"</tr>",$start+1);
      $html_genres = substr($html,$start,$end-$start);
      $matches = get_urls_from_html($html_genres,"genre");
      $new_genres = $matches[2];
      
      // Synopsis
      $start = strpos($html,"Inhalt:");
      $end = strpos($html,"</tr>",$start+1);
      $html_synopsis = substr($html,$start,$end-$start);
      // Get page with full synopsis
      $matches = array ();
      $matches = get_urls_from_html($html_synopsis,"inhalt");
      $html = file_get_contents( $site_url.$matches[1][0] );
      $synopsis  = substr_between_strings($html,'</b><br><br>','</p>');
                
      $columns = array ( "TITLE"             => $title
                       , "YEAR"              => $year
                       , "DETAILS_AVAILABLE" => 'Y'
                       , "SYNOPSIS"          => $synopsis);
          
      scdb_add_directors     ($id, $new_directors);
      scdb_add_actors        ($id, $new_actors);
      scdb_add_genres        ($id, $new_genres);
      scdb_set_movie_attribs ($id, $columns);
      return true;
    }
    else
    {
      send_to_log(2,'Failed to access the URL.');
    }
    
    // Mark the file as attempted to get details, but none available
    $columns = array ( "DETAILS_AVAILABLE" => 'N');
    scdb_set_movie_attribs ($id, $columns);
    return false;
  }
  
/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
