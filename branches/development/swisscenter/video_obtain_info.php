<?
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor
 *************************************************************************************************/

  require_once("base/page.php");
  require_once("base/db_abstract.php");
  require_once("base/utils.php");
  
  // ----------------------------------------------------------------------------------------
  // Removes common parts of filenames that we don't want to search for...
  // (eg: file extension, file suffix ("CD1",etc) and non-alphanumeric chars.
  // ----------------------------------------------------------------------------------------
  
  function strip_title ($title)
  {
    $search  = array ( '/\.[^.]*$/'
                     , '/[^0-9A-Z-a-z() ]+/'
                     , '/\(.*\)/'
                     , '/ CD.*/i'
                     , '/ +$/');
    
    $replace = array ( ''
                     , ' '
                     , ' '
                     , ' '
                     , '');
    
    return preg_replace($search, $replace, $title);
  }
    
  // ----------------------------------------------------------------------------------------
  // Gets the text that corresponds to the given detail
  // ----------------------------------------------------------------------------------------

  function get_attrib(&$text, $name)
  {
    preg_match('/'.$name.'\s{3,}(.*)\s{3,}/i',$text, $matches);
    $search  = array('/&bull;/','/&gt;[^,]*/');
    $replace = array(',','');
    
    return explode(',',preg_replace($search, $replace, $matches[1]));
  }
  
  // ----------------------------------------------------------------------------------------
  // Given a movie ID, this function will attempt to update the database within additional
  // information obtained from a source on the internet.
  // ----------------------------------------------------------------------------------------

  function extra_get_movie_details ( $file_id )
  {    

    $site_url    = 'http://www.lovefilm.com/';
    $search_url  = $site_url.'search.php?searchtype=title&dvdsearch=';
    $file_path   = db_value("select dirname from movies where file_id = $file_id");
    $file_name   = db_value("select filename from movies where file_id = $file_id");
    $film_title  = ucwords(strip_title( $file_name ));
    $html        = file_get_contents($search_url.str_replace(' ','+',$film_title));
    $accuracy    = 0;
    send_to_log('Checking movie file : '.$file_name);
        
    if (strpos($html,"Search Results") !== false)
    { 
      if (strpos($html,"no results were found") !== false)
      {
        // There are no matches found... do nothing
        $accuracy = 0;
        send_to_log("No Match found.");
      }
      else 
      {
        // There are multiple matches found... process them
        $matches = get_urls_from_html($html);
        $best_match = array("id" => 0, "chars" => 0, "pc" => 0);
        
        for ($i=0; $i<count($matches[0]); $i++)
        {
          $matches[2][$i] = strip_title($matches[2][$i]);
          $chars = similar_text($film_title,$matches[2][$i],$pc);
          $matches[2][$i] .= " (".round($pc,2)."%)";
          
          if ( $chars > $best_match["chars"] || ($chars = $best_match["chars"] && $pc > $best_match["pc"]))
            $best_match = array("id" => $i, "chars" => $chars, "pc" => $pc);
        }

        // If we are sure that we found a good result, then get the file details.
        if ($best_match["pc"] > 75)      
        {
          $html = file_get_contents($site_url.$matches[1][$best_match["id"]]);
          send_to_log('Multiple Matches found, best guess is "'.$matches[2][$best_match["id"]].'"',$matches[2]);
          $accuracy = $best_match["pc"];      
        }
        else 
        {
          send_to_log('Multiple Matches found, No match > 75%',$matches[2]);
          $accuracy = 0;
        }
      }
    }
    else 
    {
      $title = str_replace('&nbsp;','',substr_between_strings($html,'-- main body','-- Recommend'));
      similar_text($film_title, strip_title($film_title), $accuracy);
      if ($accuracy > 75)
        send_to_log('Single Match found : '.$title);      
      else 
        send_to_log('Single Match found, but not > 75%');
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
            $out = fopen($file_path.file_noext($file_name).'.jpg', "wb");
            fwrite($out, file_get_contents($site_url.$url));
            fclose($out);
            send_to_log('AlbumArt downloaded for '.$film_title);
            break;
          }
      }
      
      $details = substr_between_strings($html,"Recommend DVD","MEMBER RATINGS");
      $columns = array ( "YEAR"              => array_pop(get_attrib($details,"Year:"))
                       , "RATING"            => array_pop(get_attrib($details,"Certificate:"))
                       , "MATCH_PC"          => $accuracy
                       , "DETAILS_AVAILABLE" => 'Y');

      scdb_add_directors     (array($file_id), get_attrib($details,"Director:"));
      scdb_add_actors        (array($file_id), get_attrib($details,"Starring:"));
      scdb_add_genres        (array($file_id), get_attrib($details,"Genre\(s\):"));    
      scdb_set_movie_attribs (array($file_id), $columns);
    }
    else 
    {
      // Mark the file as attempted to get details, but none available
      $columns = array ( "MATCH_PC" => $accuracy, "DETAILS_AVAILABLE" => 'N');
      scdb_set_movie_attribs (array($file_id), $columns);
    }
  }
  
  
  //===========================================================================================
  // Main script logic
  //===========================================================================================

  function extra_get_all_movie_details ()
  {
    send_to_log('Checking online for extra movie information');
    $data = db_toarray("select file_id, filename from movies where details_available is null");
  
    // Process each movie
    foreach ($data as $row)
      extra_get_movie_details( $row["FILE_ID"] );
        
    send_to_log('Online movie check complete');
  }   

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
