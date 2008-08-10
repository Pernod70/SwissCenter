<?
/**************************************************************************************************
   SWISScenter Source
   
   This is one of a selection of scripts all designed to obtain information from the internet
   relating to the movies that the user has added to their database. It typically collects
   information such as title, genre, year of release, certificate, synopsis, directors and actors.
   
   NOTE: This parser for IMDb is _NOT_ an official part of SWISScenter, and is not supported by the
   SWISScenter developers.  If you find videos for which this parser does not work, please look to
   the forum thread in the General section, called "IMDb parser". -Nick
      http://www.swisscenter.co.uk/component/option,com_simpleboard/Itemid,42/func,view/id,3516/catid,10/

   (I suggest we keep the version sync'd with the Swisscenter release from now on, so future tweaks while
   Swisscenter is at, for example, v1.17 will be v1.17.1, v1.17.2, etc.)
   
   Version history:
   06-Aug-2008: v1.21.1:  Retrieves maximum size images (450x700) instead of (94x150).
   12-May-2008: v1.21:    Fixed certificates.
   27-Apr-2008: v1.20:    Fixed synopsis due to site change. Certificates are broken.
   09-Mar-2008: v1.19.2:  Title is now URL encoded.
   08-Jan-2008: v1.19:    Updated for new parser location and now handles multiple Directors. 
   29-May-2007: v1.17:    small change to catch up with 1.7 Swisscenter changes to album art
                            Suggest a new versioning scheme, starting now.
   21-May-2007: v1.6.2:   Include rating.php for get_rating_scheme_name
   11-May-2007: v1.6.1:   Fix credits in this source
   10-May-2007: v1.6:     Uses selected certificate scheme to get either USA or UK certificate
   09-May-2007: v1.5.1:   Choose correct filetype for album art, instead of hard-coding '.jpg'
   09-May-2007: v1.5:     If a filename contains an explicit IMDb title ID (such as '[tt0076759]'),
                            use that to locate the proper movie info
   30-Apr-2007: v1.4:     Changed Director tag from 'Directed by' to 'Director:'
   20-Feb-2007: v1.3:     Support IMDB's new user interface (which is better for parsing)
   05-Feb-2007: v1.2:     More fixes
   01-Dec-2006: v1.1:     Tweaked to support more movies. IMDB is rather unstructured in
                            its output, unfortuantely
   01-Oct-2006: v1.0:     First public release

 *************************************************************************************************/

  // Needed to provide access to the get_rating_scheme_name() function.
  require_once( SC_LOCATION.'base/rating.php' );

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
      $matches = get_urls_from_html($html, 'title\/tt\d+');
      $index   = best_match($title, $matches[2], $accuracy);

      // If we are sure that we found a good result, then get the file details.
      if ($accuracy > 75)      
      {
        $url_imdb = add_site_to_url($matches[1][$index],$site_url);
        $url_imdb = substr($url_imdb, 0, strpos($url_imdb,"?fr=")-1);
        $html = file_get_contents( $url_imdb );
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
      preg_match("'<h1>.*\(<a href=\"/Sections/Years/(\d+)\">\d*</a>(?:/[^\)]*)*\).*</h1>'ms",$html,$year);
      $start = strpos($html,"<div class=\"photo\">");
      $end = strpos($html,"<a name=\"comment\">");
      $html = substr($html,$start,$end-$start+1);
      
      // Find Synopsis
      preg_match("/<h5>Plot(| Outline| Summary):<\/h5>([^<]*)</sm",$html,$synopsis);

      // Download and store Albumart if there is none present.
      if ( file_albumart($filename, false) == '')
      {
        $matches = get_images_from_html($html);
        $img_addr = $matches[1][0];
        // Replace resize attributes with maximum allowed
        $img_addr = preg_replace('/SX\d+_/','SX450_',$img_addr);
        $img_addr = preg_replace('/SY\d+_/','SY700_',$img_addr);
        file_save_albumart( add_site_to_url($img_addr, $site_url), 
                            dirname($filename).'/'.file_noext($filename).'.'.file_ext($img_addr),
                            $title);
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
      $columns = array ( "YEAR"              => array_pop($year)
                       , "CERTIFICATE"       => db_lookup( 'certificates','name','cert_id',$rating )
                       , "MATCH_PC"          => $accuracy
                       , "DETAILS_AVAILABLE" => 'Y'
                       , "SYNOPSIS"          => trim($synopsis[2]," |"));

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

        scdb_add_directors     ($id, $new_directors);
        scdb_add_actors        ($id, $new_actors);
        scdb_add_genres        ($id, $new_genres);    
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
