<?php
/**************************************************************************************************
   SWISScenter Source

   This is one of a selection of scripts all designed to obtain information from the internet
   relating to the movies that the user has added to their database. It typically collects
   information such as title, genre, year of release, certificate, synopsis, directors and actors.

 *************************************************************************************************/

  require_once( SC_LOCATION.'/base/apple_trailers.php');

  function extra_get_movie_details($id, $filename, $title)
  {
    // Perform search for matching titles
    $site_url    = 'http://www.apple.com/trailers';

    send_to_log(4,"Searching for details about ".$title." online at '$site_url'");

    // Use Apple's internal search to get a list a possible matches
    $apple = new AppleTrailers();
    $trailers = $apple->quickFind($title);

    // Examine returned page
    if (count($trailers) == 0)
    {
      // There are no matches found... do nothing
      $accuracy = 0;
      send_to_log(4,"No Match found.");
    }
    else
    {
      $trailer_titles = array();
      foreach ($trailers as $index=>$trailer)
        $trailer_titles[$index]  = $trailer["title"];

      // There are multiple matches found... process them
      $index = best_match($title, $trailer_titles, $accuracy);
    }

    // Determine attributes for the movie and update the database
    if ($accuracy >= 75)
    {
      $synopsis = get_trailer_description($trailers[$index]);
      $year = date('Y', strtotime($trailers[$index]["releasedate"]));
      $rating = $trailers[$index]["rating"];

      $trailer_xmls = get_trailer_index($trailers[$index]);
      $trailer_urls = get_trailer_urls($trailer_xmls[1][0]);
      $trailer = array_pop($trailer_urls[2]);

      // Download and store Albumart if there is none present.
      if ( file_albumart($filename, false) == '')
        file_save_albumart( $trailers[$index]["poster"],
                            dirname($filename).'/'.file_noext($filename).'.'.file_ext($trailers[$index]["poster"]),
                            $title);

      // These are the details to be stored in the database
      $columns = array ( "YEAR"              => $year
                       , "CERTIFICATE"       => db_lookup( 'certificates','name','cert_id',$rating )
                       , "MATCH_PC"          => $accuracy
                       , "DETAILS_AVAILABLE" => 'Y'
                       , "SYNOPSIS"          => $synopsis
                       , "TRAILER"           => $trailer );

      scdb_add_directors     ($id, $trailers[$index]["director"]);
      scdb_add_actors        ($id, $trailers[$index]["actors"]);
      scdb_add_genres        ($id, $trailers[$index]["genre"]);
      scdb_set_movie_attribs ($id, $columns);
      return true;
    }
    else
    {
      // Mark the file as attempted to get details, but none available
      $columns = array ( "MATCH_PC" => $accuracy, "DETAILS_AVAILABLE" => 'N');
      scdb_set_movie_attribs ($id, $columns);
      return false;
    }
  }

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
