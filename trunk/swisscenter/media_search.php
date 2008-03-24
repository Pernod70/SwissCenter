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
  require_once( realpath(dirname(__FILE__).'/base/rss.php'));
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

  function process_media_dirs( $media_type = '', $cat_id = '', $update = false)
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
      process_media_directory( str_suffix($location["NAME"],'/'), $location["LOCATION_ID"], $table, $types, true, $update );
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
  $scan_type  = get_sys_pref('MEDIA_SCAN_TYPE');
  $rss_sub_id = get_sys_pref('MEDIA_SCAN_RSS');
  $media_type = get_sys_pref('MEDIA_SCAN_MEDIA_TYPE');
  $cat_id     = get_sys_pref('MEDIA_SCAN_CATEGORY');
  $itunes     = get_sys_pref('MEDIA_SCAN_ITUNES', true);
  $update     = get_sys_pref('REFRESH_METADATA', false);
  $itunes_library = get_sys_pref('ITUNES_LIBRARY');
  $itunes_date    = get_sys_pref('ITUNES_LIBRARY_DATE');

  delete_sys_pref('MEDIA_SCAN_RSS');
  delete_sys_pref('MEDIA_SCAN_MEDIA_TYPE');
  delete_sys_pref('MEDIA_SCAN_CATEGORY');
  delete_sys_pref('MEDIA_SCAN_ITUNES');
  delete_sys_pref('REFRESH_METADATA');
  set_sys_pref('MEDIA_SCAN_STATUS',str('MEDIA_SCAN_STATUS_RUNNING'));
  
  // Do a media search if a RSS update has not been requested
  if ($scan_type == '' || $scan_type == 'MEDIA')
  {
    // Set the percent_scanned to zero for all locations due to be scanned.
    db_sqlcommand("update media_locations set percent_scanned=0 
                    where media_table<>''".
                    (empty($cat_id) ? '' : " and cat_id = $cat_id").
                    (empty($media_type) ? '' : " and media_type = $media_type")
                 );
  
    // Scan the appropriate media directories
    process_media_dirs( $media_type, $cat_id, $update );
    
    // Update video details from the Internet if enabled
    if ( is_movie_check_enabled() )
      extra_get_all_movie_details();
    
    if ( is_tv_check_enabled() )
      extra_get_all_tv_details();
 
    // Scan the iTunes library for playlists
    if ($itunes && is_file($itunes_library))
    {
      // Date of file
      $file_date = db_datestr(@filemtime($itunes_library));
      if ( is_null($itunes_date) || ($itunes_date < $file_date) )
      {
        parse_itunes_file($itunes_library);
        set_sys_pref('ITUNES_LIBRARY_DATE', $file_date);
      }
      else
        send_to_log(4,'Skipping the iTunes Music Library, not changed since last update');
    }
  }
    
  // Update RSS feeds
  if ($scan_type == '' || $scan_type == 'RSS')
  {
    if (internet_available() && get_sys_pref('rss_enabled','YES') == 'YES')
    {
      // Set the percent_scanned to zero for all subscriptions due to be scanned.
      db_sqlcommand("update rss_subscriptions set percent_scanned=0 where 1=1".
                    (empty($rss_sub_id) ? '' : " and id = $rss_sub_id")
                   );
    
      // Update the appropriate rss subscriptions
      rss_update_subscriptions($rss_sub_id);
    }
  }
  
  remove_orphaned_records();
  remove_orphaned_movie_info();
  remove_orphaned_tv_info();
  scdb_remove_orphans();
  eliminate_duplicates();
  media_indicator('OFF');

  // Update media search status
  set_sys_pref('MEDIA_SCAN_STATUS',str('MEDIA_SCAN_STATUS_COMPLETE'));
  db_sqlcommand("update media_locations set percent_scanned = null");
  db_sqlcommand("update rss_subscriptions set percent_scanned = null");

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
