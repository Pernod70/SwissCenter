<?php
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor
 *************************************************************************************************/

  require_once( realpath(dirname(__FILE__).'/base/db_abstract.php'));
  require_once( realpath(dirname(__FILE__).'/base/utils.php'));
  require_once( realpath(dirname(__FILE__).'/base/rating.php'));
  require_once( realpath(dirname(__FILE__).'/base/xml_sidecar.php'));
  require_once( realpath(dirname(__FILE__).'/ext/parsers/ParserMovieLookup.php'));
  require_once( realpath(dirname(__FILE__).'/ext/parsers/ParserTvLookup.php'));

  // ----------------------------------------------------------------------------------------
  // Gets the value of an attrbute for a particluar tag (often the "alt" of an "img" tag)
  // ----------------------------------------------------------------------------------------

  function get_html_tag_attrib( $html, $tag, $find, $attribute )
  {
    $tag_html = array();
    preg_match ('`<.*'.$tag.'.*'.$find.'.*>`Ui', $html, $tag_html);
    preg_match ('`'.$attribute.'="(.*)"`Ui',$tag_html[0],$val);
    if (isset($val[1]) && !empty($val[1]))
      return $val[1];
    else
      return false;
  }

  function get_html_tag_value( $html, $tag, $find)
  {
    $val = array();
    preg_match ('`<.*'.$tag.'.*'.$find.'.*>(.*)</'.$tag.'>`Ui', $html, $val);
    if (isset($val[1]) && !empty($val[1]))
      return $val[1];
    else
      return false;
  }

  // ----------------------------------------------------------------------------------------
  // Function to perform a search by movie title. There are a number of parameters that need
  // to be passed to this function to control how the search is done (detailed below). This
  // function returns either FALSE (no match >75%) or the HTML from the page pointed to by
  // the best match.
  //
  // $title -- The movie title to search for.
  // $site_url -- The address of the site (eg: http://amazon.co.uk/)
  // $search_url -- The URL used to perform a search, with ##### where the movie name should go
  // $success_text -- A seach is deemed to be successful if a page is returned which contains this text
  // $link_string -- Part of the href that indicates a link is to the full details.
  // $change_word_order -- if TRUE then titles such as "The Abyss" will be changed to "Abyss, The".
  // strip_title -- if TRUE then titles returned from the site are stripped of text in brackets. ie."300 [Blu-ray]" becomes "300".
  // ----------------------------------------------------------------------------------------

  function search_for_movie( $title, $site_url, $search_url, $success_text, $link_string, $change_word_order = false, $strip_title = false )
  {
    $film_title  = ucwords(strip_title( $title ));
    $accuracy = 0;

    send_to_log(4,"Searching for details about '$film_title' online at '$site_url'");

    // Change the word order?
    if ( $change_word_order && substr($film_title,0,3)=='The' )
      $film_title = substr($film_title,4).' The';

    // Submit the search
    $search_page = $site_url.str_replace('#####',urlencode($film_title),$search_url);
    send_to_log(6,'Fetching information from:',parse_url($search_page));
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
        $matches = get_urls_from_html($html, $link_string);
        if ($strip_title)
          $matches[2] = preg_replace(array('/\(.*\)/','/\[.*]/'), '', $matches[2]);
        $index = best_match($film_title, $matches[2], $accuracy);

        if ($index === false)
          $html = false;
        else
        {
          $url = add_site_to_url($matches[1][$index],$site_url);
          send_to_log(6,'Fetching information from:',parse_url($url));
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
    db_sqlcommand("delete from languages_of_movie where movie_id = $movie_id ");
    db_sqlcommand("update movies set year=null, details_available='N', match_pc=null, certificate=null, synopsis=null, external_rating_pc=null where file_id = $movie_id");
  }

  /**
   * Function to remove all details from the database regarding the specified tv show
   *
   * @param integer $tv_id
   */

  function purge_tv_details( $tv_id )
  {
    db_sqlcommand("delete from actors_in_tv where tv_id = $tv_id ");
    db_sqlcommand("delete from directors_of_tv where tv_id = $tv_id ");
    db_sqlcommand("delete from genres_of_tv where tv_id = $tv_id ");
    db_sqlcommand("delete from languages_of_tv where tv_id = $tv_id ");
    db_sqlcommand("update tv set title=null, year=null, details_available='N', certificate=null, synopsis=null, external_rating_pc=null where file_id = $tv_id");
  }

  /**
   * This function gets the movie details for all movies in the database where the
   * details_available flag is not set. (ie: no lookup has taken place).
   *
   */

  function extra_get_all_movie_details( $cat_id = '' )
  {
    if ( is_movie_check_enabled() )
    {
      send_to_log(4,'Checking online for extra movie information');

      // Only try to update movie information for categories that have it enabled, and where the details_available column is null.
      $data = db_toarray("select file_id
                               , dirname, filename
                               , title
                            from movies m, media_locations ml, categories c
                           where m.location_id = ml.location_id
                             and ml.cat_id = c.cat_id
                             and m.details_available is null".
    (empty($cat_id) ? '' : ' and c.cat_id = '.$cat_id).
                           " and c.download_info = 'Y' ");

      // Process each movie
      foreach ($data as $row)
      {
        // DVD Video details are stored in the parent folder
        if ( strtoupper($row["FILENAME"]) == 'VIDEO_TS.IFO' )
          $filename = rtrim($row["DIRNAME"],'/').".xml";
        else
          $filename = $row["DIRNAME"].$row["FILENAME"];

        ParserMovieLookup( $row["FILE_ID"], $filename, array('TITLE' => $row["TITLE"],
                                                             'YEAR'  => $row["YEAR"]) );
        // Export to XML
        if ( get_sys_pref('movie_xml_save','NO') == 'YES' )
          export_video_to_xml( $row["FILE_ID"] );
      }

      send_to_log(4,'Online movie check complete');
    }
    else
      send_to_log(4,'Online movie check is DISABLED');
  }

  /**
   * This function gets the tv show details for all tv shows in the database where the
   * details_available flag is not set. (ie: no lookup has taken place).
   *
   */

  function extra_get_all_tv_details( $cat_id = '' )
  {
    if ( is_tv_check_enabled() )
    {
      send_to_log(4,'Checking online for extra tv information');

      // Only try to update tv information where the details_available column is null.
      $data = db_toarray("select file_id
                               , concat(dirname,filename) fsp
                               , programme
                               , episode
                               , series
                               , title
                            from tv t, media_locations ml, categories c
                           where t.location_id = ml.location_id
                             and ml.cat_id = c.cat_id
                             and t.details_available is null".
    (empty($cat_id) ? '' : ' and c.cat_id = '.$cat_id).
                           " and c.download_info = 'Y' ");

      // Process each tv show
      foreach ($data as $row)
      {
        ParserTvLookup( $row["FILE_ID"], $row["FSP"], array('PROGRAMME' => $row["PROGRAMME"],
                                                            'SERIES'    => $row["SERIES"],
                                                            'EPISODE'   => $row["EPISODE"],
                                                            'TITLE'     => $row["TITLE"]) );
        // Export to XML
        if ( get_sys_pref('tv_xml_save','NO') == 'YES' )
          export_tv_to_xml( $row["FILE_ID"] );
      }

      send_to_log(4,'Online tv show check complete');
    }
    else
      send_to_log(4,'Online tv show check is DISABLED');
  }

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
