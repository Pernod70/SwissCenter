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
   01-Feb-2008: v1.3:     Improved accuracy of search results.

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
    $search_url   = 'http://www.google.de/search?q=#####+site%3Aofdb.de';
    $search_title = str_replace('%20','+',urlencode($title));
    
    send_to_log(4,"Searching for details about ".$title." online at '$site_url'");
                            
    // Get page from ofdb.de using Google search
    $url_load = str_replace('#####',$search_title,$search_url);
    send_to_log(6,'Fetching information from: '.$url_load);
    $html     = file_get_contents( $url_load );

    if ($html === false)
    {
      send_to_log(2,'Failed to access the URL.');
    }
    else
    {
      // Is the text that signifies a successful search present within the HTML?    
      if (strpos(strtolower($html),strtolower('Ergebnisse')) !== false)
      {
        $matches = get_urls_from_html($html, '');
        $index   = best_match('OFDb '.$title, $matches[2], $accuracy);
        
        if ($index === false)
          $html = false;
        else
        {
          $ofdb_url = add_site_to_url($matches[1][$index],$site_url);
          // If search result contains film id and is not film page then adjust URL to be film page.
          if (strpos($ofdb_url,'film')==false && strpos($ofdb_url,'fid')!==false)
          {
            $ofdb_url = str_replace('fassung','film',$ofdb_url);
            $ofdb_url = str_replace('inhalt','film',$ofdb_url);
            $ofdb_url = str_replace('review','film',$ofdb_url);
          }
          send_to_log(6,'Fetching information from: '.$ofdb_url);
          $html = file_get_contents( $ofdb_url );
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
        $img_addr = get_html_tag_attrib($html,'img','images/film/','src');
        if ($img_addr !== false)
          file_save_albumart( add_site_to_url($img_addr, $site_url)
                            , dirname($filename).'/'.file_noext($filename).'.'.file_ext($img_addr)
                            , $title);
      }

      // Year
      $start = strpos($html,"Erscheinungsjahr:");
      $end = strpos($html,"</tr>",$start+1);
      $html_year = substr($html,$start,$end-$start);
      $matches = get_urls_from_html($html_year,"Jahr");
      $year = $matches[2][0];

      // Director(s)
      $start = strpos($html,"Regie:");
      $end = strpos($html,"</tr>",$start+1);
      $html_directed = substr($html,$start,$end-$start);
      $matches = array ();
      $matches = get_urls_from_html($html_directed,"Name");
      scdb_add_directors ( $id, $matches[2] );
        
      // Actor(s)
      $start = strpos($html,"Darsteller:");
      $end = strpos($html,"</tr>",$start+1);
      $html_actors = substr($html,$start,$end-$start);
      $matches = array ();
      $matches = get_urls_from_html($html_actors,"Name");
      scdb_add_actors ( $id, $matches[2] );
      
      // Genre
      $start = strpos($html,"Genre(s):");
      $end = strpos($html,"</tr>",$start+1);
      $html_genres = substr($html,$start,$end-$start);
      $matches = get_urls_from_html($html_genres,"genre");
      scdb_add_genres ( $id, $matches[2] );
      
      // Synopsis
      $start = strpos($html,"Inhalt:");
      $end = strpos($html,"</tr>",$start+1);
      $html_synopsis = substr($html,$start,$end-$start);
      // Get page with full synopsis
      $matches = get_urls_from_html($html_synopsis,"inhalt");
      $html = file_get_contents( $site_url.$matches[1][0] );
      $synopsis  = substr_between_strings($html,'</b><br><br>','</p>');
 
      // Store the single-value movie attributes in the database
      $columns = array ( "YEAR"              => $year
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
    $columns = array ( "DETAILS_AVAILABLE" => 'N');
    scdb_set_movie_attribs ($id, $columns);
    return false;
  }
  
/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
