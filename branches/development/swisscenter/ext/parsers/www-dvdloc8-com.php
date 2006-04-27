<?
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor
 *************************************************************************************************/

  require_once( realpath(dirname(__FILE__).'/base/page.php'));
  require_once( realpath(dirname(__FILE__).'/video_obtain_info.php'));
  require_once( realpath(dirname(__FILE__).'/base/weather.php'));

  function extra_get_movie_details($id, $filename, $title)
  {
    // The site URL (may be used later)
    $site_url    = 'http://www.dvdloc8.com/';
    
    // Determine the best match for the movie and retrieve the HTML for relevant the details page.
    $html = search_for_movie( $title
                            , $site_url
                            , 'fast_search_action.php?keywords=#####&searchtype=title'
                            , 'results of your search'
                            , 'viewdvd'
                            , true );
                        
    // Did we managed to get a HTML page containing the details?                                
    if ($html !== false)
    {
                                        
      // Determine the URL of the albumart and attempt to download it.
      $img_addr = get_html_tag_attrib($html,'img','images/dvdcover','src');
      if ($img_addr !== false)
        file_save_albumart( add_site_to_url($img_addr, $site_url)
                          , file_noext($filename).'.'.file_ext($img_addr)
                          , $title);
    
      // Get moviethe single-value movie attributes 
      $cert_text = get_html_tag_attrib($html,'img','images/rating','alt');
      $synopsis  = substr_between_strings($html,'Synopsis:','<br><br>');
      $year      = substr_between_strings($html,'Year of Production:','DVD Release Date:');

      // Store the single-value movie attributes in the database
      scdb_set_movie_attribs( $id , array ( 'YEAR'        => $year
                                          , 'CERTIFICATE' => db_lookup( 'certificates','name','cert_id', $cert_text )
                                          , 'SYNOPSIS'    => $synopsis
                                          ));
      
      // Find and set multi-value movie attributes (genres, actors and directors)
      scdb_add_directors ( $id, explode("\n", substr_between_strings($html,'Directed By:','Recommended Retail Price')) );
      scdb_add_actors    ( $id, explode("\n", substr_between_strings($html,'Cast:','Genre:')) );
      scdb_add_genres    ( $id, explode ('/', substr_between_strings($html,'Genre:','Running Time:')) );
    
      // Store the fact that there are details available in the database.
      scdb_set_movie_attribs( $id, array( 'DETAILS_AVAILABLE' => 'Y') );
    }
  }

  #####################################
  
  $title    = 'Hitch';
  $id       = db_value("select file_id from movies where title='$title'");
  $filename = db_value("select concat(dirname,filename) from movies where file_id=$id");

  extra_get_movie_details($id, $filename, $title);  

  debug( db_toarray("select * from movies where file_id=$id"));
  
/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
