<?
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor
 *************************************************************************************************/

  require_once( realpath(dirname(__FILE__).'/base/page.php'));
  require_once( realpath(dirname(__FILE__).'/base/db_abstract.php'));
  require_once( realpath(dirname(__FILE__).'/base/utils.php'));
  require_once( realpath(dirname(__FILE__).'/base/rating.php'));
  
  // ----------------------------------------------------------------------------------------
  // Gets the value of an attrbute for a particluar tag (often the "alt" of an "img" tag)
  // ----------------------------------------------------------------------------------------

  function get_html_tag_attrib( $html, $tag, $find, $attribute )
  {
    preg_match ('¬<.*'.$tag.'.*'.$find.'.*>¬Ui', $html, &$tag_html);
    preg_match ('¬'.$attribute.'="(.*)"¬Ui',$tag_html[0],$val);
    if (isset($val[1]) && !empty($val[1]))
      return $val[1];
    else 
      return false;
  }

  function get_html_tag_value( $html, $tag, $find)
  {
    preg_match ('¬<.*'.$tag.'.*'.$find.'.*>(.*)</'.$tag.'>¬Ui', $html, &$val);
    if (isset($val[1]) && !empty($val[1]))
      return $val[1];
    else 
      return false;
  }
  
  // ----------------------------------------------------------------------------------------
  // Given a string to search for ($needle) and an array of possible matches ($haystack) this
  // function will return the index number of the best match and set $accuracy to the value
  // determined (0-100). If no match is found, then this function returns FALSE
  // ----------------------------------------------------------------------------------------

  function best_match ( $needle, $haystack, &$accuracy )
  {    
    $best_match = array("id" => 0, "chars" => 0, "pc" => 0);
      
    for ($i=0; $i<count($haystack); $i++)
    {
      $haystack[$i] = strip_title($haystack[$i]);
      $chars = similar_text($needle,$haystack[$i],$pc);
      $haystack[$i] .= " (".round($pc,2)."%)";
      
      if ( ($chars > $best_match["chars"] && $pc >= $best_match["pc"]) || $pc > $best_match["pc"])
        $best_match = array("id" => $i, "chars" => $chars, "pc" => $pc);
    }

    // If we are sure that we found a good result, then get the file details.
    if ($best_match["pc"] > 75)      
    {
      send_to_log(6,'Possible matches are:',$haystack);
      send_to_log(4,'Best guess: ['.$best_match["id"].'] - '.$haystack[$best_match["id"]]);
      $accuracy = $best_match["pc"];      
      return $best_match["id"];
    }
    else 
    {
      send_to_log(4,'Multiple Matches found, No match > 75%',$haystack);
      return false;
    }          
  }
  
  // ----------------------------------------------------------------------------------------
  // Function to perform a search by movie title. There are a number of parameters that need
  // to be passed to this function to control how the search is done (detailed below). This
  // function returns either FALSE (no match >75%) or the HTML from the page pointed to by
  // the best match.
  //
  // £title -- The movie title to search for. 
  // $site_url -- The address of the site (eg: http://amazon.co.uk/)
  // $search_url -- The URL used to perform a search, with ##### where the movie name should go
  // $success_text -- A seach is deemed to be successful if a page is returned which contains this text
  // $link_string -- Part of the href that indicates a link is to the full details.
  // $change_word_order -- if TRUE then titles such as "The Abyss" will be changed to "Abyss, The".
  // ----------------------------------------------------------------------------------------

  function search_for_movie ( $title, $site_url, $search_url, $success_text, $link_string, $change_word_order = false)
  {
    $film_title  = ucwords(strip_title( $title ));
    $accuracy = 0;
    
    send_to_log(4,"Searching for details about '$film_title' online at '$site_url'");

    // Change the word order?
    if ( $change_word_order && substr($film_title,0,3)=='The' )
      $film_title = substr($film_title,4).' The';

    // Submit the search
    $search_page = $site_url.str_replace('#####',urlencode($film_title),$search_url);
    send_to_log(6,'Fetching information from:',url_get_components($search_page));
    $html        = file_get_contents( $search_page);    

    if ($html === false)
    {
      send_to_log(2,'Failed to access the URL.');
    }
    else
    {
      // Is the text that signifies a successful search present within the HTML?    
      if (strpos(strtolower($html),strtolower($success_text)) !== false)
      {
        $matches     = get_urls_from_html($html, $link_string);
        $index       = best_match($film_title, $matches[2], $accuracy);
        
        if ($index === false)
          $html = false;          
        else
        {
          $url = add_site_to_url($matches[1][$index],$site_url);
          send_to_log(6,'Fetching information from:',url_get_components($url));
          $html = file_get_contents( $url );
        }
      }
      else
      {
        send_to_log(4,"No Match found.");
        $html = false;
      }
    }
    
    return $html;
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
  
  /**
   * Function to remove all details from the database regarding the specified movie
   *
   * @param integer $movie_id
   */
  
  function purge_movie_details( $movie_id )
  {
    db_sqlcommand("delete from actors_in_movie where movie_id = $movie_id ");
    db_sqlcommand("delete from directors_of_movie where movie_id = $movie_id ");
    db_sqlcommand("delete from genres_of_movie where movie_id = $movie_id ");
    db_sqlcommand("update movies set year=null, details_available='N', match_pc=null, certificate=null, synopsis=null where file_id = $movie_id");
  }
  
  // ----------------------------------------------------------------------------------------
  // This function gets the movie details for all movies in the database where the 
  // details_available flag is not set. (ie: no lookup has taken place).
  // ----------------------------------------------------------------------------------------

  function extra_get_all_movie_details ()
  {
    if ( is_movie_check_enabled() )
    {
      send_to_log(4,'Checking online for extra movie information from '.file_noext(get_sys_pref('movie_info_script','www.dvdloc8.com.php')));

      // Only try to update movie information for categories that have it enabled, and where the details_available column is null.
      $data = db_toarray("select file_id
                               , concat(dirname,filename) fsp
                               , filename title
                            from movies m, media_locations ml, categories c
                           where m.location_id = ml.location_id
                             and ml.cat_id = c.cat_id
                             and m.details_available is null
                             and  c.download_info = 'Y' ");
    
      // Process each movie
      foreach ($data as $row)
        extra_get_movie_details( $row["FILE_ID"], $row["FSP"], $row["TITLE"] );
          
      send_to_log(4,'Online movie check complete');
    }
    else 
      send_to_log(4,'Online movie check is DISABLED');
  }   


  // ----------------------------------------------------------------------------------------
  // Determine which movie database the user has requested that we use.
  // ----------------------------------------------------------------------------------------

  $parser_dir = realpath( dirname(__FILE__).'/ext/parsers' );

  if ( isset($_REQUEST["parser"]) && !empty($_REQUEST["parser"]) )
  {
    $inc_file=$_REQUEST["parser"];
  }
  else 
  {
    $inc_file   = get_sys_pref('movie_info_script','www.dvdloc8.com.php');
    if ( !file_exists($parser_dir.'/'.$inc_file) )
      $inc_file = 'www.dvdloc8.com.php';
  }

  // Include the appropriate file
  send_to_log(4,'Including parser file '.$parser_dir.'/'.$inc_file);
  require_once( $parser_dir.'/'.$inc_file );
  
  /**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
