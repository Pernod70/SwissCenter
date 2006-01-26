<?
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor
 *************************************************************************************************/

  require_once( realpath(dirname(__FILE__).'/base/page.php'));
  require_once( realpath(dirname(__FILE__).'/base/db_abstract.php'));
  require_once( realpath(dirname(__FILE__).'/base/utils.php'));
  
  // ----------------------------------------------------------------------------------------
  // Removes common parts of filenames that we don't want to search for...
  // (eg: file extension, file suffix ("CD1",etc) and non-alphanumeric chars.
  // ----------------------------------------------------------------------------------------
  
  function strip_title ($title)
  {
    $search  = array ( '/\.[^.]*$/'
                     , '/\(.*\)/'
                     , '/\[.*]/'
                     , '/[^0-9A-Z-a-z() ]+/'
                     , '/ CD.*/i'
                     , '/ +$/');
    
    $replace = array ( ''
                     , ' '
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
  // This function gets the movie details for all movies in the database where the 
  // details_available flag is not set. (ie: no lookup has taken place).
  // ----------------------------------------------------------------------------------------

  function extra_get_all_movie_details ()
  {
    if (get_sys_pref('movie_check_enabled','YES') == 'YES')
    {
      send_to_log('Checking online for extra movie information');
      $data = db_toarray("select file_id, filename from movies where details_available is null");
    
      // Process each movie
      foreach ($data as $row)
        extra_get_movie_details( $row["FILE_ID"] );
          
      send_to_log('Online movie check complete');
    }
    else 
      send_to_log('Online movie check is DISABLED');
  }   

  // ----------------------------------------------------------------------------------------
  // Determine which movie database the user has requested that we use.
  // ----------------------------------------------------------------------------------------

  require_once( get_sys_pref('movie_info_script','movie_lovefilm.php'));
  
  /**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
