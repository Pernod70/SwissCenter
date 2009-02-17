<?php
/**************************************************************************************************
   SWISScenter Source

   This is one of a selection of scripts all designed to obtain information from the internet
   relating to movies that the user has added to their database. It typically collects
   information such as genre, year of release, synopsis, directors and actors.

 *************************************************************************************************/

require_once( SC_LOCATION."/ext/json/json.php");

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
    $site_url = 'http://www.ofdb.de/';
    $accuracy = 0;

    // Get search results from google.
    send_to_log(4,"Searching for details about ".$title." online at '$site_url'");
    $results = google_api_search($title,"ofdb.de");

    // Change the word order
    if ( substr($title,0,4)=='The ' ) $title = substr($title,5).', The';
    if ( substr($title,0,4)=='Der ' ) $title = substr($title,5).', Der';
    if ( substr($title,0,4)=='Die ' ) $title = substr($title,5).', Die';
    if ( substr($title,0,4)=='Das ' ) $title = substr($title,5).', Das';

    if (count($results)==0)
    {
      send_to_log(4,"No Match found.");
      $html = false;
    }
    else
    {
      $best_match = google_best_match('OFDb - '.$title,$results,$accuracy);

      if ($best_match === false)
        $html = false;
      else
      {
        $ofdb_url = $best_match->url;

        // If search result contains film id and is not film page then adjust URL to be film page.
        if (strpos($ofdb_url,'film')==false && strpos($ofdb_url,'fid')!==false)
        {
          $ofdb_url = str_replace('fassung','film',$ofdb_url);
          $ofdb_url = str_replace('inhalt','film',$ofdb_url);
          $ofdb_url = str_replace('review','film',$ofdb_url);
        }
        send_to_log(6,'Fetching information from: '.$ofdb_url);
        $html = utf8_decode(file_get_contents( $ofdb_url ));
      }
    }

    if ($html != false)
    {
      send_to_log(4,"Found details for '".substr_between_strings($html,'<title>','</title>')."'");

      // Determine the URL of the albumart and attempt to download it.
      if ( file_albumart($filename, false) == '')
      {
        $img_addr = get_html_tag_attrib($html,'img','img.ofdb.de/film/','src');
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
      if ($start !== false)
      {
        $matches = get_urls_from_html($html_synopsis,"plot");
        send_to_log(6,'Fetching information from: ',$site_url.$matches[1][0]);
        $html = utf8_decode(file_get_contents( $site_url.$matches[1][0] ));
        $synopsis  = substr_between_strings($html,'</b><br><br>','</p>');
      }
      else
        $synopsis = '';

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
