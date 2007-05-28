<?
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor
 *************************************************************************************************/

  require_once( realpath(dirname(__FILE__).'/base/settings.php'));
  require_once( realpath(dirname(__FILE__).'/base/page.php'));
  require_once( realpath(dirname(__FILE__).'/base/mysql.php'));
  require_once( realpath(dirname(__FILE__).'/base/utils.php'));
  require_once( realpath(dirname(__FILE__).'/base/media.php'));
  require_once( realpath(dirname(__FILE__).'/video_obtain_info.php'));

  set_time_limit(0);
  ini_set('memory_limit',-1);
  $start_time = time();
  $cache_dir = get_sys_pref('cache_dir');

  // ----------------------------------------------------------------------------------
  // Processes each media location in turn, setting the verified flag and deleting
  // records which are no longer available.
  // ----------------------------------------------------------------------------------

  function process_media_dirs( $media_type )
  {
    $media_locations = db_toarray("select * from media_locations where media_type=$media_type");
    $table           = db_value("select media_table from media_types where  media_id=$media_type");
    $types           = media_exts( $media_type );

    send_to_log(4,'Refreshing '.strtoupper($table).' database');
      
    foreach ($media_locations as $location)
      process_media_directory( str_suffix($location["NAME"],'/'), $location["LOCATION_ID"], $table, $types );
        
    send_to_log(4,'Completed refreshing '.strtoupper($table).' database');
  }

  //===========================================================================================
  // Main script logic
  //===========================================================================================

  media_indicator('BLINK');
  db_sqlcommand("update media_locations set percent_scanned=0 where media_type in (".MEDIA_TYPE_MUSIC.",".MEDIA_TYPE_VIDEO.",".MEDIA_TYPE_PHOTO.")");
  
  process_media_dirs( MEDIA_TYPE_MUSIC );
  process_media_dirs( MEDIA_TYPE_VIDEO );
  process_media_dirs( MEDIA_TYPE_PHOTO );

  if ( is_movie_check_enabled() )
    extra_get_all_movie_details();
  
  remove_orphaned_records();
  remove_orphaned_movie_info();
  eliminate_duplicates();
  media_indicator('OFF');
 
  // Tell the MusicIP server (if present) to refresh it's cache and validate any new songs.
  musicip_server_refresh_cache();
  musicip_server_validate();

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
