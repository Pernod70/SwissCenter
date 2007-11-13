<?php
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor
 *************************************************************************************************/

  require_once( realpath(dirname(__FILE__).'/base/settings.php'));
  require_once( realpath(dirname(__FILE__).'/base/page.php'));
  require_once( realpath(dirname(__FILE__).'/base/mysql.php'));
  require_once( realpath(dirname(__FILE__).'/base/utils.php'));
  require_once( realpath(dirname(__FILE__).'/base/media.php'));
  require_once( realpath(dirname(__FILE__).'/base/musicip.php'));
  require_once( realpath(dirname(__FILE__).'/video_obtain_info.php'));
  require_once( realpath(dirname(__FILE__).'/itunes_import.php'));

  set_time_limit(86400);
  ini_set('memory_limit',-1);
  $start_time = time();
  $cache_dir = get_sys_pref('cache_dir');

  // ----------------------------------------------------------------------------------
  // Processes each media location in turn, setting the verified flag and deleting
  // records which are no longer available.
  // ----------------------------------------------------------------------------------

  function process_media_dirs( $media_type = '', $cat_id = '')
  {
    // Get a list of matching locations
    $media_locations = db_toarray("select * 
                                   from media_locations
                                   where (media_type not in (".MEDIA_TYPE_RADIO.",".MEDIA_TYPE_WEB."))".
                                   (empty($cat_id) ? '' : " and cat_id = $cat_id").
                                   (empty($media_type) ? '' : " and media_type = $media_type")
                                 );

    // Process each location
    foreach ($media_locations as $location)
    {
      $table = db_value("select media_table from media_types where  media_id = $location[MEDIA_TYPE]");
      $types = media_exts( $location["MEDIA_TYPE"] );
      send_to_log(4,'Refreshing '.strtoupper($table).' database');
      process_media_directory( str_suffix($location["NAME"],'/'), $location["LOCATION_ID"], $table, $types );
      send_to_log(4,'Completed refreshing '.strtoupper($table).' database');
      
      // Tell MusicIP to rescan this folder
      if ($media_type == MEDIA_TYPE_MUSIC)
        musicip_server_add_dir($location["NAME"]);    
  }

  }

  //===========================================================================================
  // Main script logic
  //===========================================================================================

  media_indicator('BLINK');
  
  // If there are parameters for the media search then read them and then remove them.
  $media_type = get_sys_pref('MEDIA_SCAN_MEDIA_TYPE');
  $cat_id     = get_sys_pref('MEDIA_SCAN_CATEGORY');
  $itunes_library = get_sys_pref('ITUNES_LIBRARY');

  delete_sys_pref('MEDIA_SCAN_MEDIA_TYPE');
  delete_sys_pref('MEDIA_SCAN_CATEGORY');
  set_sys_pref('MEDIA_SCAN_STATUS',str('MEDIA_SCAN_STATUS_RUNNING'));
  
  // Set the percent_scanned to zero for all locations due to be scanned.
  db_sqlcommand("update media_locations set percent_scanned=0 
                  where (media_type not in (".MEDIA_TYPE_RADIO.",".MEDIA_TYPE_WEB."))".
                  (empty($cat_id) ? '' : " and cat_id = $cat_id").
                  (empty($media_type) ? '' : " and media_type = $media_type")
               );

  // Scan the appropriate media directories
  process_media_dirs( $media_type, $cat_id );

  // Scan the iTunes library for playlists
  if (is_file($itunes_library))
    parse_itunes_file($itunes_library);
  
  // update video details from the Internet if enabled
  if ( is_movie_check_enabled() )
    extra_get_all_movie_details();
  
  remove_orphaned_records();
  remove_orphaned_movie_info();
  eliminate_duplicates();
  media_indicator('OFF');

  // Update media search status
  set_sys_pref('MEDIA_SCAN_STATUS',str('MEDIA_SCAN_STATUS_COMPLETE'));
  db_sqlcommand("update media_locations set percent_scanned = null");

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
