<?php
/**************************************************************************************************
   SWISScenter Source
   
   This is one of a selection of scripts all designed to obtain information from the internet
   relating to movies that the user has added to their database. It typically collects
   information such as genre, year of release, synopsis, directors and actors.
   
   Version history:
   26-May-2008: v1.0:     First public release

 *************************************************************************************************/

  /**
   * Searches the filmaffinity.com site for movie details
   *
   * @param integer $id
   * @param string $filename
   * @param string $title
   * @return bool
   */
  function extra_get_movie_details($id, $filename, $title)
  {
    // The site URL (may be used later)
    $site_url     = 'http://www.filmaffinity.com/';
    $search_url   = 'http://www.filmaffinity.com/es/search.php?stext=#####&stype=title';
    $search_title = str_replace('%20','+',urlencode($title));
    
    send_to_log(4,"Searching for details about ".$title." online at '$site_url'");
                            
    // Get page from filmaffinity.com
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
      if (strpos(strtolower($html),strtolower('Resultados por título')) !== false)
      {
        preg_match_all ('/<b><a.*href="(.*\/es\/film\d+\.html[^"]*)"[^>]*>(.*)<\/a>/Ui', $html, &$matches);
//        $matches = get_urls_from_html($html, '\/es\/film\d+\.html');
        $index   = best_match($title, $matches[2], $accuracy);
        
        if ($index === false)
          $html = false;
        else
        {
          $film_url = add_site_to_url($matches[1][$index],$site_url);
          send_to_log(6,'Fetching information from: '.$film_url);
          $html = file_get_contents( $film_url );
        }
      }
    }
    
    if ($html !== false)
    {
      send_to_log(4,"Found details for '".substr_between_strings($html,'<title>','</title>')."'");  
      
      // Determine the URL of the albumart and attempt to download it.
      if ( file_albumart($filename, false) == '')
      {
        $img_addr = get_html_tag_attrib($html,'img','/imgs/movies/full/','src');
        if ($img_addr !== false)
          file_save_albumart( $img_addr, dirname($filename).'/'.file_noext($filename).'.'.file_ext($img_addr), $title);
      }

      // Year
      $year = substr_between_strings($html,'AÑO','</tr>');

      // Director(s)
      $start = strpos($html,"DIRECTOR");
      $end = strpos($html,"</tr>",$start+1);
      $html_directed = substr($html,$start,$end-$start);
      $matches = array ();
      $matches = get_urls_from_html($html_directed,"director");
      scdb_add_directors ( $id, $matches[2] );
        
      // Actor(s)
      $start = strpos($html,"REPARTO");
      $end = strpos($html,"</tr>",$start+1);
      $html_actors = substr($html,$start,$end-$start);
      $matches = array ();
      $matches = get_urls_from_html($html_actors,"cast");
      scdb_add_actors ( $id, $matches[2] );
      
      // Genre
//      $start = strpos($html,"GÉNERO Y CRÍTICA");
//      $end = strpos($html,"SINOPSIS:",$start+1);
//      $genres = explode('.',substr($html,$start,$end-$start));
//      scdb_add_genres ( $id, $genres );
      
      // Synopsis
      $synopsis = substr_between_strings($html,'SINOPSIS:','(FILMAFFINITY)');
 
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
