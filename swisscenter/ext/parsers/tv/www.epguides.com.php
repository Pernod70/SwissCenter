<?php
/**************************************************************************************************
   SWISScenter Source
   
   This is one of a selection of scripts all designed to obtain information from the internet
   relating to tv series that the user has added to their database. It typically collects
   information such as title, genre, year of release, synopsis, directors and actors.
   
   This parser actually uses three sites to obtain the required data in the following steps:
   1. Uses Googles 'I'm Feeling Lucky' with 'site:epguides.com %series_name%' to obtain page at
      epguides.com containing list of all episodes for required series. The cast photo is 
      downloaded from here.
   2. Page from epguides.com is searched for link containing 'full_summary' which provides link to 
      series summary page at tv.com. This is used to retrieve genre and year of first showing. 
   3. Page from epguides.com is searched for required episode which provides link to episode page 
      at tv.com. This is then parsed for director, actors, and synopsis.
      
   NOTE: This parser for TV.com is _NOT_ an official part of SWISScenter, and is not supported by the
   SWISScenter developers. Nigel (Pernod)
   
   Version history:
   26-Sep-2007: v1.0:     First public release
   11-Jan-2008: v1.1:     Stores series and episode if match was found by title.
   28-Jan-2008: v1.2:     Encoded URL containing Programme name and improved search and error handling.

 *************************************************************************************************/

  /**
   * Searches the tv.com site for tv episode details
   *
   * @param integer $id
   * @param string $filename
   * @param string $programme
   * @param string $series
   * @param string $episode
   * @param string $title
   * @return bool
   */
  function extra_get_tv_details($id, $filename, $programme, $series='', $episode='', $title='')
  {
    // First letter of number for menu selection
    $num_char     = array('1'=>'o','2'=>'t','3'=>'t','4'=>'f','5'=>'f','6'=>'s','7'=>'s','8'=>'e','9'=>'n');
    // The site URL (may be used later)
    $site_url     = 'http://epguides.com/';
    $search_url   = 'http://www.google.com/search?q=site%3Aepguides.com&q=+Titles+#####';
    $search_title = str_replace('%20','+',urlencode($programme));
    
    send_to_log(4,"Searching for details about ".$programme." Season: ".$series." Episode: ".$episode." online at ".$site_url);                   
    
    // Form URL of epguides menu page
    if (preg_match("([a-zA-Z])",$programme{0}) > 0)
      $url_load = $site_url.'menu'.$programme{0};
    elseif  (preg_match("([1-9])",$programme{0}) > 0)
      $url_load = $site_url.'menu'.$num_char[$programme{0}];
    else
      $url_load = null;

    // Get epguides menu page and search for match
    if (!empty($url_load))
    {
      send_to_log(6,'Fetching information from: '.$url_load);
      $html     = file_get_contents( $url_load );
      $matches  = get_urls_from_html($html, '');
      $index    = best_match(ucwords(strtolower($programme)), $matches[2], $accuracy);
      if ($index === false)
        $epguides_url = false;          
      else
        $epguides_url = add_site_to_url($matches[1][$index],$url_load);
    }
    
    // Get page from epguides.com using Google search (only if previous search on menu page failed)
    if (empty($epguides_url))
    {
      $url_load = str_replace('#####',$search_title,$search_url);
      send_to_log(6,'Fetching information from: '.$url_load);
      $html     = file_get_contents( $url_load );
      
      if ($html === false)
      {
        send_to_log(2,'Failed to access the URL.');
      }
      else
      {
        // Is the text that signifies a successful search present within the HTML?    
        if (strpos(strtolower($html),strtolower('Results')) !== false)
        {
          // Determine URL of first returned item
          $start        = strpos($html, $site_url);
          $end          = strpos($html,'"',$start+1);
          $epguides_url = substr($html,$start,$end-$start);
          $epguides_url = substr($epguides_url,0,strrpos($epguides_url,'/'));
        }
        else
        {
          send_to_log(4,"No Match found.");
          $epguides_url = false;
        }
      }
    }
    
    send_to_log(6,'Fetching information from: '.$epguides_url);
    $html = file_get_contents( $epguides_url );
    
    if ($html != false)
    {
      // Determine the URL of the albumart and attempt to download it.
      if ( file_albumart($filename, false) == '')
      {
        $matches = get_images_from_html($html);
        for ($i = 0; $i<count($matches[1]); $i++)
        {
          if ((strpos($matches[1][$i],'cast') !== false) || (strpos($matches[1][$i],'logo') !== false && strpos($matches[1][$i],'NO_logo') == false))
          {
            file_save_albumart( add_site_to_url($matches[1][$i], $epguides_url)
                              , dirname($filename).'/'.file_noext($filename).'.'.file_ext($matches[1][$i])
                              , $series);
            break;
          }
        }
      }
    
      // Check that page contains links to tv.com
      if (strpos($html,'www.tv.com') !== false)
      {    
        // Get link for tv.com Summary
        $matches = get_urls_from_html($html, 'full_summary=1');
        if (count($matches[1]) == 0)
          $matches = get_urls_from_html($html, 'ShowMainServlet');
        if (count($matches[1]) > 0)
        {
          // Get page containing Series Summary
          $url_load = substr($matches[1][0],strrpos_str($matches[1][0],'http'));
          $url_load = $matches[1][0];
          send_to_log(6,'Fetching information from: '.$url_load);
          $html_summary = file_get_contents( $url_load );
            
          // Genre (allowing for Category and Categories)
          $start = strpos($html_summary,"Show Categor");
          $end = strpos($html_summary,"</div>",$start+1);
          $html_genres = substr($html_summary,$start,$end-$start);
          $matches = get_urls_from_html($html_genres,"genre");
          scdb_add_tv_genres ( $id, $matches[2] );
        }
        else 
          send_to_log(6,'Cannot find link to tv.com summary page for specified series.');
        
        // Search for link for required Episode by series-episode
        $start = strpos($html,$series.'-'.sprintf("%2s", $episode));
        if ($start !== false)
        {
          $end = strpos($html,"</pre>",$start+1);
          $html = substr($html,$start,$end-$start);
        }
        $matches = get_urls_from_html($html, 'summary.html');

        // Couldn't find episode so try to match episode title
        if ($start === false && $title <> '')
        {
          $index = best_match(ucwords(strtolower($title)), $matches[2], $accuracy);  
          if ($index !== false)
          {
            $end = strpos(strtolower($html),strtolower($matches[2][$index]));
            $start = strrpos(substr($html,0,$end),"<a")-40;
            $end = strpos($html,"</pre>",$start+1);
            $html_episode = substr($html,$start,$end-$start);
            $sep = strpos($html_episode,'-');
            $series  = substr($html_episode,$sep-2,2);
            if (!is_numeric($series)) $series = 0;
            $episode = substr($html_episode,$sep+1,2);
            if (!is_numeric($episode)) $episode = 0;
            $matches = get_urls_from_html($html_episode, 'summary.html');
          }
        }
        
        if ($start !== false && count($matches[1])>0)
        {
          // Get page containing Episode Summary
          $url_load = substr($matches[1][0],strrpos_str($matches[1][0],'http'));
          $title    = $matches[2][0];
          send_to_log(6,'Fetching information from: '.$url_load);
          $html = file_get_contents( $url_load );
                
          // Year
          $year  = substr_between_strings($html,'First Aired:','&nbsp');
          $year  = substr($year,strlen($year)-4);

          // Synopsis
          $start = strpos($html,'<div id="main-col">');
          $end = strpos($html,'class="ta-r mt-10 f-bold">',$start+1);
          $html_synopsis = substr($html,$start,$end-$start);
          $start = strrpos_str($html_synopsis,'div>');
          $html_synopsis = substr($html_synopsis,$start);
          $synopsis_ep  = substr_between_strings($html_synopsis,'div>','<div');

          // Director(s)
          $start = strpos($html,"Director:");
          $end = strpos($html,"<tr>",$start+1);
          $html_directed = substr($html,$start,$end-$start);
          $matches = get_urls_from_html($html_directed,"summary.html");
          scdb_add_tv_directors ( $id, $matches[2] );
        
          // Actor(s)
          $start = strpos($html,"Star:");
          $end = strpos($html,"<tr>",$start+1);
          $html_actors = substr($html,$start,$end-$start);
          $matches = get_urls_from_html($html_actors,"summary.html");
          scdb_add_tv_actors ( $id, $matches[2] );
          
          // Store the single-value movie attributes in the database   
          $columns = array ( "TITLE"             => $title
                           , "SERIES"            => $series
                           , "EPISODE"           => $episode
                           , "YEAR"              => $year
                           , "DETAILS_AVAILABLE" => 'Y'
                           , "SYNOPSIS"          => $synopsis_ep);
          scdb_set_tv_attribs  ($id, $columns);
          return true;
        }
        else
        {
          send_to_log(4,"Cannot find link to tv.com Episode page.");
        }
      }
      // Check that page contains links to epguides.com
      elseif (strpos($html,'epguides.com') !== false)
      { 
        // Get link for required Episode
        $matches = get_urls_from_html($html, 'guide.shtml');
        
        // Get link for required Episode
        $start = strpos($html,$series.'-'.sprintf("%2s", $episode));
        if ($start !== false)
        {
          $end = strpos($html,"</pre>",$start+1);
          $html_episode = substr($html,$start,$end-$start);
          $matches = get_urls_from_html($html_episode, 'guide.shtml');
        }
        else 
          $matches = array(); 
              
        if (count($matches[1])>0)
        {
          // Get page containing Episode Summary
          $url_load = add_site_to_url($matches[1][0],$epguides_url);
          $title    = $matches[2][0];
          send_to_log(6,'Fetching information from: '.$url_load);
          $html = file_get_contents( $url_load );
          
          // Determine the URL of the albumart and attempt to download it.
          if ( file_albumart($filename, false) == '')
          {
            $matches = get_images_from_html($html);
            for ($i = 0; $i<count($matches[1]); $i++)
            {
              if ((strpos($matches[1][$i],'cast') !== false) || (strpos($matches[1][$i],'logo') !== false && strpos($matches[1][$i],'NO_logo') == false))
              {
                file_save_albumart( add_site_to_url($matches[1][$i], $epguides_url)
                                  , dirname($filename).'/'.file_noext($filename).'.'.file_ext($matches[1][$i])
                                  , $series);
                break;
              }
            }
          }
          
          // Crop returned page to required episode
          $start = strpos($html,$series.'-'.sprintf("%2s", $episode));
          if ($start !== false)
          {
            $end = strpos($html,"</pre>",$start+1);
            $html_episode = substr($html,$start,$end-$start);
            
            // Year
            $year  = substr_between_strings($html,'First Aired:','&nbsp');
            $year  = substr($year,strlen($year)-4);
  
            // Synopsis
            $start = strpos($html,'<div id="main-col">');
            $end = strpos($html,'class="ta-r mt-10 f-bold">',$start+1);
            $html_synopsis = substr($html,$start,$end-$start);
            $start = strrpos_str($html_synopsis,'div>');
            $html_synopsis = substr($html_synopsis,$start);
            $synopsis_ep  = substr_between_strings($html_synopsis,'div>','<div');
  
            // Director(s)
            $start = strpos($html,"Director:");
            $end = strpos($html,"<tr>",$start+1);
            $html_directed = substr($html,$start,$end-$start);
            $matches = get_urls_from_html($html_directed,"summary.html");
            scdb_add_tv_directors ( $id, $matches[2] );
          
            // Actor(s)
            $start = strpos($html,"Star:");
            $end = strpos($html,"<tr>",$start+1);
            $html_actors = substr($html,$start,$end-$start);
            $matches = get_urls_from_html($html_actors,"summary.html");
            scdb_add_tv_actors ( $id, $matches[2] );
            
            // Store the single-value movie attributes in the database
            $columns = array ( "TITLE"             => $title
                             , "YEAR"              => $year
                             , "DETAILS_AVAILABLE" => 'Y'
                             , "SYNOPSIS"          => $synopsis_ep);
            scdb_set_tv_attribs  ($id, $columns);
            return true;
          }
          else 
          {
            send_to_log(4,"Cannot find details for specified episode at epguides.com.");
          }
        }
        else
        {
          send_to_log(4,"Cannot find link to epguides.com Episode page.");
        }      
      }
      else
      {
        send_to_log(4,"Cannot find links for series/episodes.");
      }
    }
    else
    {
      send_to_log(4,"Cannot find link to epguides.com Programme page.");
    }
    
    // Mark the file as attempted to get details, but none available
    $columns = array ( "TITLE"             => $title
                     , "DETAILS_AVAILABLE" => 'N');
    scdb_set_tv_attribs ($id, $columns);
    return false;
  }
  
/**************************************************************************************************
                                               End of file
 **************************************************************************************************/

?>
