<?php
/**************************************************************************************************
   SWISScenter Source
   
   This is one of a selection of scripts all designed to obtain information from the internet
   relating to movies that the user has added to their database. It typically collects
   information such as genre, year of release, synopsis, directors and actors.
      
   NOTE: This parser for FilmUP.com is _NOT_ an official part of SWISScenter, and is not supported by the
   SWISScenter developers. Nigel Barnes(Pernod)
   
   Version history:
   27-Jan-2008: v1.0:     First public release.
   01-Feb-2008: v1.1:     Improved accuracy of search results.

 *************************************************************************************************/

  /**
   * Searches the filmup.it site for movie details
   *
   * @param integer $id
   * @param string $filename
   * @param string $title
   * @return bool
   */
  function extra_get_movie_details($id, $filename, $title)
  {
    // The site URL (may be used later)
    $site_url     = 'http://filmup.leonardo.it/';
    $search_url   = 'http://www.google.it/search?q=+Scheda%3A+#####+site%3Afilmup.leonardo.it';
    $search_title = str_replace('%20','+',urlencode($title));
    
    send_to_log(4,"Searching for details about ".$title." online at '$site_url'");
      
    // Get page from filmup.it using Google search
    $url_load = str_replace('#####',$search_title,$search_url);
    send_to_log(6,'Fetching information from: '.$url_load);
    $html     = file_get_contents( $url_load );
    $accuracy = 0;
    
    if ($html === false)
    {
      send_to_log(2,'Failed to access the URL.');
    }
    else
    {
      // Is the text that signifies a successful search present within the HTML?    
      if (strpos(strtolower($html),strtolower('Risultati')) !== false)
      {
        $matches = get_urls_from_html($html, '');
        $index   = best_match('FilmUP Scheda '.$title, $matches[2], $accuracy);
        
        if ($index === false)
          $html = false;          
        else
        {
          $filmup_url = add_site_to_url($matches[1][$index],$site_url);
          send_to_log(6,'Fetching information from: '.$filmup_url);
          $html = file_get_contents( $filmup_url );
        }
      }
      else
      {
        send_to_log(4,"No Match found.");
        $html = false;
      }
    }  
      
    if ($html != false)
    {
      send_to_log(4,"Found details for '".substr_between_strings($html,'<title>','</title>')."'");  
      
      // Determine the URL of the albumart and attempt to download it.
      if ( file_albumart($filename, false) == '')
      {
        $img_addr = get_html_tag_attrib($html,'img','locand/','src');
        if ($img_addr !== false)
          file_save_albumart( add_site_to_url($img_addr, $site_url)
                            , dirname($filename).'/'.file_noext($filename).'.'.file_ext($img_addr)
                            , $title);
      }
           
      // Find and set multi-value movie attributes (genres, actors and directors)        
      scdb_add_directors ( $id, explode(",", substr_between_strings($html,'Regia:&nbsp;','Sito ufficiale:')) );
      scdb_add_actors    ( $id, explode(",", substr_between_strings($html,'Cast:&nbsp;','Produzione:')) );
      scdb_add_genres    ( $id, explode(",", substr_between_strings($html,'Genere:&nbsp;','Durata:')) );
        
      // Get single-value movie attributes 
      $synopsis = substr_between_strings($html,'Trama:<br>','<br>'); //'</f');
      $year     = substr_between_strings($html,'Anno:&nbsp;','Genere:');

      // Store the single-value movie attributes in the database
      $columns = array ( "YEAR"              => $year
                       , "MATCH_PC"          => $accuracy
                       , "DETAILS_AVAILABLE" => 'Y'
                       , "SYNOPSIS"          => $synopsis);
      scdb_set_movie_attribs ($id, $columns);
      return true;  
    }
    else
    {
      send_to_log(2,'Failed to access the URL.');
    }
    
    // Mark the file as attempted to get details, but none available
    $columns = array ( "MATCH_PC" => $accuracy, "DETAILS_AVAILABLE" => 'N');
    scdb_set_movie_attribs ($id, $columns);
    return false;
  }
  
/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
