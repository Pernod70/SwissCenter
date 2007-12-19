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

 *************************************************************************************************/

  /**
   * Searches the tv.com site for tv episode details
   *
   * @param integer $id
   * @param string $filename
   * @param string $programme
   * @param string $series
   * @param string $episode
   * @return bool
   */
  function extra_get_tv_details($id, $filename, $programme, $series='', $episode='')
  {
    // The site URL (may be used later)
    $site_url     = 'http://www.epguides.com/';
    $search_url   = 'http://www.google.com/search?hl=en&q=&q=site%3Aepguides.com&q=#####&btnI=Search';
    $search_title = str_replace(' ','+',$programme);
//    $file_path    = db_value("select dirname from tv where file_id = $id");
//    $file_name    = db_value("select filename from tv where file_id = $id");
    
    send_to_log(4,"Searching for details about ".$programme." Season: ".$series." Episode: ".$episode." online at ".$site_url);                   
                            
    // Get page from epguides.com using Google 'I'm Feeling Lucky'
    $url_load = str_replace('#####',$search_title,$search_url);
    $html     = file_get_contents( $url_load );

    if ($html != false)
    {
      // Download and store Albumart if there is none present (cast photo)
//      if ( file_albumart($file_path.$file_name) == '')
//      {
        $matches = array ();
        $matches = get_images_from_html($html);
        for ($i = 0; $i<count($matches[1]); $i++)
        {
          if ((strpos($matches[1][$i],'cast') !== false) || (strpos($matches[1][$i],'logo') !== false))
          {
            $pos = strpos($matches[1][$i], "http://");
//            $orig_ext = file_ext($matches[1][$i]);
//            file_save_albumart( ($pos===false ? $site_url : "").$matches[1][$i] , $file_path.file_noext($file_name).'.'.$orig_ext , $series);
          }
        }
//      }
    
      // Check that page contains links to tv.com
      if (strpos($html,'www.tv.com') !== false)
      {    
        // Get link for tv.com Summary
        $matches = get_urls_from_html($html, 'full_summary=1');
        
        // Get page containing Series Summary
        $url_load = substr($matches[1][0],strrpos_str($matches[1][0],'http'));
        send_to_log(6,'Fetching information from:',parse_url($url_load));
        $html_summary = file_get_contents( $url_load );
            
        // Genre (allowing for Category and Categories)
        $start = strpos($html_summary,"Show Categor");
        $end = strpos($html_summary,"</div>",$start+1);
        $html_genres = substr($html_summary,$start,$end-$start);
        $matches = get_urls_from_html($html_genres,"genre");
        $new_genres = $matches[2];
      
        // Get link for required Episode
        $start = strpos($html,$series.'-'.sprintf("%2s", $episode));
        $end = strpos($html,"</pre>",$start+1);
        $html_episode = substr($html,$start,$end-$start);
        $matches = get_urls_from_html($html_episode, 'summary.html');
              
        if (count($matches[1])>0)
        {
          // Get page containing Episode Summary
          $url_load = substr($matches[1][0],strrpos_str($matches[1][0],'http'));
          $title    = $matches[2][0];
          send_to_log(6,'Fetching information from:',parse_url($url_load));
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
          $matches = array ();
          $matches = get_urls_from_html($html_directed,"summary.html");
          $new_directors = $matches[2];
        
          // Actor(s)
          $start = strpos($html,"Star:");
          $end = strpos($html,"<tr>",$start+1);
          $html_actors = substr($html,$start,$end-$start);
          $matches = array ();
          $matches = get_urls_from_html($html_actors,"summary.html");
          $new_actors = $matches[2];
                
          $columns = array ( "TITLE"             => $title
                           , "YEAR"              => $year
                           , "DETAILS_AVAILABLE" => 'Y'
                           , "SYNOPSIS"          => $synopsis_ep);
          
          scdb_add_tv_directors($id, $new_directors);
          scdb_add_tv_actors   ($id, $new_actors);
          scdb_add_tv_genres   ($id, $new_genres);    
          scdb_set_tv_attribs  ($id, $columns);
          return true;
        }
        else
        {
          send_to_log(4,"Cannot find specified episode.");
        }
      }
      // Check that page contains links to epguides.com
      elseif (strpos($html,'epguides.com') !== false)
      { 
        // Get link for required Episode
        $matches = get_urls_from_html($html, 'guide.shtml');
        
        // Get link for required Episode
        $start = strpos($html,$series.'-'.sprintf("%2s", $episode));
        $end = strpos($html,"</pre>",$start+1);
        $html_episode = substr($html,$start,$end-$start);
        $matches = get_urls_from_html($html_episode, 'guide.shtml');
              
        if (count($matches[1])>0)
        {
          // Get page containing Episode Summary
          $url_load = substr($matches[1][0],strrpos_str($matches[1][0],'http'));
          $title    = $matches[2][0];
          send_to_log(6,'Fetching information from:',parse_url($url_load));
          $html = file_get_contents( $url_load );
          
          // Crop returned page to required episode
          $start = strpos($html,$series.'-'.sprintf("%2s", $episode));
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
          $matches = array ();
          $matches = get_urls_from_html($html_directed,"summary.html");
          $new_directors = $matches[2];
        
          // Actor(s)
          $start = strpos($html,"Star:");
          $end = strpos($html,"<tr>",$start+1);
          $html_actors = substr($html,$start,$end-$start);
          $matches = array ();
          $matches = get_urls_from_html($html_actors,"summary.html");
          $new_actors = $matches[2];
                
          $columns = array ( "TITLE"             => $title
                           , "YEAR"              => $year
                           , "DETAILS_AVAILABLE" => 'Y'
                           , "SYNOPSIS"          => $synopsis_ep);
          
          scdb_add_tv_directors($id, $new_directors);
          scdb_add_tv_actors   ($id, $new_actors);
          scdb_add_tv_genres   ($id, $new_genres);    
          scdb_set_tv_attribs  ($id, $columns);
          return true;
        }
        else
        {
          send_to_log(4,"Cannot find specified episode.");
        }      
      }
      else
      {
        send_to_log(4,"Cannot find specified series.");
      }
    }
    else
    {
      send_to_log(2,'Failed to access the URL.');
    }
    
    // Mark the file as attempted to get details, but none available
    $columns = array ( "DETAILS_AVAILABLE" => 'N');
    scdb_set_tv_attribs ($id, $columns);
    return false;
  }
  
/**************************************************************************************************
                                               End of file
 **************************************************************************************************/

?>
