<?
/**************************************************************************************************
   SWISScenter Source

   This is one of a selection of scripts all designed to obtain information from the internet
   relating to the movies that the user has added to their database. It typically collects
   information such as title, genre, year of release, certificate, synopsis, directors and actors.

 *************************************************************************************************/

  function extra_get_movie_details($id, $filename, $title)
  {
    // Perform search for matching titles
    $site_url    = 'http://www.imdb.com/';
    $search_url  = $site_url.'find?s=tt;q=';
    $details     = db_toarray("select * from movies where file_id=$id");

    send_to_log(4,"Searching for details about ".$title." online at '$site_url'");

    if (preg_match("/\[(tt\d+)\]/",$filename, $imdbtt) != 0)
    {
      // Filename includes an explicit IMDb title such as '[tt0076759]', use that to find the movie
      $html = file_get_contents($search_url.$imdbtt[1]);
    }
    elseif (preg_match("/\[(tt\d+)\]/",$details[0]["TITLE"], $imdbtt) != 0)
    {
      // Film title includes an explicit IMDb title such as '[tt0076759]', use that to find the movie
      $html = file_get_contents($search_url.$imdbtt[1]);
    }
    else
    {
      // User IMDb's internal search to get a list a possible matches
      $html = file_get_contents($search_url.str_replace('%20','+',urlencode($title)));
    }

    // Decode HTML entities found on page
    $html = html_entity_decode($html);

    // If the title contains a year in brackets ie.(1978) then adjust the returned page to include year in search
    if (preg_match("/\((\d+)\)/",$details[0]["TITLE"],$title_year) != 0)
    {
      $html = preg_replace('/<\/a>\s+\((\d\d\d\d).*\)/Ui',' ($1)</a>',$html);
      $title .= ' '.$title_year[0];
    }

    // Examine returned page
    if (strpos(strtolower($html),"no matches") !== false)
    {
      // There are no matches found... do nothing
      $accuracy = 0;
      send_to_log(4,"No Match found.");
    }
    else if (strpos($html, "<title>IMDb Title Search</title>") > 0)
    {
      // There are multiple matches found... process them
      $html    = substr($html,strpos($html,"Titles"));
      $matches = get_urls_from_html($html, '\/title\/tt\d+\/');
      $index   = best_match($title, $matches[2], $accuracy);

      // If we are sure that we found a good result, then get the file details.
      if ($accuracy > 75)
      {
        $url_imdb = add_site_to_url($matches[1][$index],$site_url);
        $url_imdb = substr($url_imdb, 0, strpos($url_imdb,"?fr=")-1);
        $html = html_entity_decode(file_get_contents( $url_imdb ));
      }
    }
    else
    {
      // Direct hit on the title
      $accuracy = 100;
    }

    // Determine attributes for the movie and update the database
    if ($accuracy >= 75)
    {
      $year = preg_get('#href=\"/Sections/Years/(\d+)#',$html);
      $start = strpos($html,"<div class=\"photo\">");
      $end = strpos($html,"<a name=\"comment\">");
      $html = substr($html,$start,$end-$start+1);

      // Find Synopsis
      preg_match("/<h5>Plot(| Outline| Summary):<\/h5>([^<]*)</sm",$html,$synopsis);

      // Find User Rating
      $user_rating = preg_get("/<h5>User Rating:<\/h5>.*?<b>(.*)\/10<\/b>/sm",$html);

      // Download and store Albumart if there is none present.
      if ( file_albumart($filename, false) == '')
      {
        $matches = get_images_from_html($html);
        $img_addr = $matches[1][0];
        if (file_ext($img_addr)=='jpg')
        {
          // Replace resize attributes with maximum allowed
          $img_addr = preg_replace('/SX\d+_/','SX450_',$img_addr);
          $img_addr = preg_replace('/SY\d+_/','SY700_',$img_addr);
          file_save_albumart( add_site_to_url($img_addr, $site_url),
                              dirname($filename).'/'.file_noext($filename).'.'.file_ext($img_addr),
                              $title);
        }
      }

      // Parse the list of certificates
      $certlist = array();
      foreach ( explode('|', substr_between_strings($html,'Certification:','</div>')) as $cert)
      {
        $country = trim(substr($cert,0,strpos($cert,':')));
        $certificate = trim(substr($cert,strpos($cert,':')+1)).' ';
        $certlist[$country] = substr($certificate,0,strpos($certificate,' '));
      }

      // Try to get the rating in the exact certificate scheme used by the user if possible.
      if (get_rating_scheme_name() == 'BBFC')
        $rating = ( isset($certlist["UK"]) ? $certlist["UK"] : $certlist["USA"]);
      elseif (get_rating_scheme_name() == 'MPAA')
        $rating = ( isset($certlist["USA"]) ? $certlist["USA"] : $certlist["UK"]);

      // These are the details to be stored in the database
      $columns = array ( "YEAR"              => $year
                       , "CERTIFICATE"       => db_lookup( 'certificates','name','cert_id',$rating )
                       , "EXTERNAL_RATING_PC"=> (empty($user_rating) ? '' : $user_rating * 10 )
                       , "MATCH_PC"          => $accuracy
                       , "DETAILS_AVAILABLE" => 'Y'
                       , "SYNOPSIS"          => trim(trim($synopsis[2])," |"));

      // Attempt to capture the fact that the website has changed and we are unable to get movie information.
      if (strlen($html) == 0)
      {
        send_to_log(1,'UNABLE TO GET MOVIE INFORMATION FROM WWW.IMDB.COM');
        send_to_log(1,'This may be due to IMDB changing their page format - IMDB is not supported by');
        send_to_log(1,'the Swisscenter developers, so please don\'t ask them for help.');
        return false;
      }
      else
      {
        // Director(s)
        $start = strpos($html,"<h5>Director");
        $end = strpos($html,"<h5>",$start+1);
        $html_directed = substr($html,$start,$end-$start);
        $matches = get_urls_from_html($html_directed,"\/name\/nm\d+\/");
        $new_directors = $matches[2];

        // Actor(s)
        $start = strpos($html,"<table class=\"cast\">");
        $end = strpos($html,"</table>",$start+1);
        $html_actors = substr($html,$start,$end-$start);
        $matches = get_urls_from_html($html_actors,"\/name\/nm\d+\/");
        for ($i=0; $i<count($matches[2]); $i++)
        {
          if (strlen($matches[2][$i]) == 0)
          {
            array_splice($matches[2],$i,1);
            $i--;
          }
        }
        $new_actors = $matches[2];

        // Genre
        $start = strpos($html,"<h5>Genre:</h5>");
        $end = strpos($html,"</div>",$start+1);
        $html_genres = substr($html,$start,$end-$start);
        $matches = get_urls_from_html($html_genres,"\/Sections\/Genres\/");
        $new_genres    = $matches[2];

        // Languages
        $start = strpos($html,"<h5>Language:</h5>");
        $end = strpos($html,"</div>",$start+1);
        $html_langs = str_replace("\n","",substr($html,$start,$end-$start));
        $matches = get_urls_from_html($html_langs,"\/Sections\/Languages\/");
        $new_languages = $matches[2];

        scdb_add_directors     ($id, $new_directors);
        scdb_add_actors        ($id, $new_actors);
        scdb_add_genres        ($id, $new_genres);
        scdb_add_languages     ($id, $new_languages);
        scdb_set_movie_attribs ($id, $columns);
        return true;
      }
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
