<?php
/**************************************************************************************************
   SWISScenter Source

   This is one of a selection of scripts all designed to obtain information from the internet
   relating to movies that the user has added to their database. It typically collects
   information such as genre, year of release, synopsis, directors and actors.

 *************************************************************************************************/

require_once( SC_LOCATION."/ext/json/json.php");

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
    $site_url = 'http://filmup.leonardo.it/';
    $accuracy = 0;

    // Get search results from google.
    send_to_log(4,"Searching for details about ".$title." online at '$site_url'");
    $results = google_api_search("Scheda: ".$title,"filmup.leonardo.it");

    if (count($results)==0)
    {
      send_to_log(4,"No Match found.");
      $html = false;
    }
    else
    {
      $best_match = google_best_match('FilmUP - Scheda: '.$title, $results, $accuracy);

      if ($best_match === false)
        $html = false;
      else
      {
        $filmup_url = $best_match->url;
        send_to_log(6,'Fetching information from: '.$filmup_url);
        $html = file_get_contents( $filmup_url );
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
      scdb_add_directors ( $id, explode(",", html_entity_decode(substr_between_strings($html,'Regia:&nbsp;</font>','</font>'), ENT_QUOTES)) );
      scdb_add_actors    ( $id, explode(",", html_entity_decode(substr_between_strings($html,'Cast:&nbsp;</font>','</font>'), ENT_QUOTES)) );
      scdb_add_genres    ( $id, explode(",", html_entity_decode(substr_between_strings($html,'Genere:&nbsp;</font>','</font>'), ENT_QUOTES)) );

      // Get single-value movie attributes
      $synopsis = html_entity_decode(substr_between_strings($html,'Trama:<br>','<br>'), ENT_QUOTES);
      $year     = substr_between_strings($html,'Anno:&nbsp;</font>','</font>');

      // Get link for rating
      $opinioni_uid = preg_get('/opinioni\/op.php\?uid=(\d+)"/', $html);
      if (!empty($opinioni_uid))
      {
        $html = file_get_contents( $site_url.'opinioni/op.php?uid='.$opinioni_uid );
        $user_rating = preg_get('/Media Voto:.*<b>(.*)<\/b>/Uism', $html);
      }

      // Store the single-value movie attributes in the database
      $columns = array ( "YEAR"              => $year
                       , "EXTERNAL_RATING_PC"=> (empty($user_rating) ? '' : $user_rating * 10 )
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
