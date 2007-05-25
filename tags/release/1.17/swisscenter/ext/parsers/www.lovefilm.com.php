<?
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor
   
   This is one of a selection of scripts all designed to obtain information from the internet
   relating to the movies that the user has added to their database. It typically collects
   information such as title, genre, year of release, certificate, synopsis, directors and actors.

 *************************************************************************************************/

  function extra_get_movie_details($id, $filename, $title)
  {    
    // First check to see if we've encountered an error downloading the details before in this session.
    if ( isset($_SESSION['Movie_info_download']) )
      return;

    // Perform search for matching titles
    $site_url    = 'http://www.lovefilm.com/';
    $search_url  = $site_url.'search.php?searchtype=title&dvdsearch=';
    $file_path   = db_value("select dirname from movies where file_id = $id");
    $file_name   = db_value("select filename from movies where file_id = $id");
    $film_title  = ucwords(strip_title( $file_name ));
    $html        = @file_get_contents($search_url.str_replace(' ','+',$film_title));
    $accuracy    = 0;
    send_to_log(4,'Checking movie file : '.$file_name);
        
    if (strpos(strtolower($html),"search results") !== false)
    { 
      if (strpos(strtolower($html),"no results were found") !== false)
      {
        // There are no matches found... do nothing
        $accuracy = 0;
        send_to_log(4,"No Match found.");
      }
      else 
      {
        // There are multiple matches found... process them
        
        $html = substr($html,strpos($html,"Film Title"));     
        $matches = get_urls_from_html($html, 'view_dvd');
        $best_match = array("id" => 0, "chars" => 0, "pc" => 0);
        
        for ($i=0; $i<count($matches[0]); $i++)
        {
          $matches[2][$i] = strip_title($matches[2][$i]);
          $chars = similar_text($film_title,$matches[2][$i],$pc);
          $matches[2][$i] .= " (".round($pc,2)."%)";
          
          if ( ($chars > $best_match["chars"] && $pc >= $best_match["pc"]) || $pc > $best_match["pc"])
            $best_match = array("id" => $i, "chars" => $chars, "pc" => $pc);
        }

        // If we are sure that we found a good result, then get the file details.
        if ($best_match["pc"] > 75)      
        {
          $html = file_get_contents($site_url.$matches[1][$best_match["id"]]);
          send_to_log(4,'Multiple Matches found, best guess is "'.$matches[2][$best_match["id"]].'"',$matches[2]);
          $accuracy = $best_match["pc"];      
        }
        else 
        {
          send_to_log(4,'Multiple Matches found, No match > 75%',$matches[2]);
          $accuracy = 0;
        }
      }
    }
    else 
    {
      $title = str_replace('&nbsp;','',substr_between_strings($html,'-- main body','-- Recommend'));
      similar_text($film_title, strip_title($film_title), $accuracy);
      if ($accuracy > 75)
        send_to_log(4,'Single Match found : '.$title);      
      else 
        send_to_log(4,'Single Match found, but not > 75%');
    }
    
    // Determine attributes for the movie and update the database
    if ($accuracy >= 75)
    {
      // Download and store Albumart if there is none present.
      if ( file_albumart($file_path.$file_name) == '')
      {
        $matches = get_images_from_html($html);
        foreach ($matches[1] as $url)
          if (strpos($url,'boxcover')>0)
          {
            file_save_albumart( $site_url.$url , $file_path.file_noext($file_name).'.jpg' , $film_title);
            break;
          }
      }
      
      $details = substr_between_strings($html,"boxcover-padded","<h2>");
      $columns = array ( "YEAR"              => array_pop(get_attrib($details,"Year:"))
                       , "CERTIFICATE"       => db_lookup( 'certificates','name','cert_id',array_pop(get_attrib($details,"Certificate:")) )
                       , "MATCH_PC"          => $accuracy
                       , "DETAILS_AVAILABLE" => 'Y'
                       , "SYNOPSIS"          => substr_between_strings($details,'',chr(9)));
                       
      // Attempt to capture the fact that the website has changed and we are unable to get movie information.
      if (strlen($details) == 0)
      {
        send_to_log(1,'UNABLE TO GET MOVIE INFORMATION FROM WWW.LOVEFILM.COM');
        send_to_log(1,'This may be due to lovefilm changing their page format - please post to the forums on');
        send_to_log(1,'the www.swisscenter.co.uk website requesting that the matter be investigated further.');  
        $_SESSION['Movie_info_download'] = true;              
        return false;
      }
      else
      {
        $new_directors = get_attrib($details,"Director:");
        $new_actors    = get_attrib($details,"Starring:");
        $new_genres    = get_attrib($details,"Genre\(s\):");
        
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
