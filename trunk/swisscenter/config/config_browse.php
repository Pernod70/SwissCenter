<?
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor
 *************************************************************************************************/
    
  // ----------------------------------------------------------------------------------
  // Displays the various connectivity options
  // ----------------------------------------------------------------------------------

  function browse_display($message = '')
  {
    $option_vals  = array( str('ENABLED')=>'YES',str('DISABLED')=>'NO');
    
    echo "<h1>".str('BROWSE_OPTIONS')."</h1>";
    message($message);    
    echo '<p>'.str('BROWSE_TEXT');

    // Individual settings
    
    form_start('index.php', 150, 'conn');
    form_hidden('section', 'BROWSE');
    form_hidden('action', 'SET_OPTS');
    form_label('<p>&nbsp;<br><b>'.str('BROWSE_MOVIES').'</b>','L');
    form_radio_static('video_title',str('BROWSE_TITLE'),$option_vals,get_sys_pref('browse_video_title_enabled','YES'),false,true);
    form_radio_static('video_actor',str('BROWSE_ACTOR'),$option_vals,get_sys_pref('browse_video_actor_enabled','YES'),false,true);
    form_radio_static('video_director',str('BROWSE_DIRECTOR'),$option_vals,get_sys_pref('browse_video_director_enabled','YES'),false,true);
    form_radio_static('video_genre',str('BROWSE_GENRE'),$option_vals,get_sys_pref('browse_video_genre_enabled','YES'),false,true);
    form_radio_static('video_year',str('BROWSE_YEAR'),$option_vals,get_sys_pref('browse_video_year_enabled','YES'),false,true);
    form_radio_static('video_certificate',str('BROWSE_CERTIFICATE'),$option_vals,get_sys_pref('browse_video_certificate_enabled','YES'),false,true);
    form_radio_static('video_filesystem',str('BROWSE_FILESYSTEM'),$option_vals,get_sys_pref('browse_video_filesystem_enabled','YES'),false,true);
    
    form_label('<p>&nbsp;<br><b>'.str('BROWSE_MUSIC').'</b>','L');
    form_radio_static('music_artist',str('BROWSE_ARTIST'),$option_vals,get_sys_pref('browse_music_artist_enabled','YES'),false,true);
    form_radio_static('music_album',str('BROWSE_ALBUM'),$option_vals,get_sys_pref('browse_music_album_enabled','YES'),false,true);
    form_radio_static('music_track',str('BROWSE_TRACK'),$option_vals,get_sys_pref('browse_music_track_enabled','YES'),false,true);
    form_radio_static('music_genre',str('BROWSE_GENRE'),$option_vals,get_sys_pref('browse_music_genre_enabled','YES'),false,true);
    form_radio_static('music_year',str('BROWSE_YEAR'),$option_vals,get_sys_pref('browse_music_year_enabled','YES'),false,true);
    form_radio_static('music_filesystem',str('BROWSE_FILESYSTEM'),$option_vals,get_sys_pref('browse_music_filesystem_enabled','YES'),false,true);
    
    form_label('<p>&nbsp;<br><b>'.str('BROWSE_PHOTOS').'</b>','L');
    form_radio_static('photo_album',str('BROWSE_PHOTO_ALBUM'),$option_vals,get_sys_pref('browse_photo_album_enabled','YES'),false,true);
    form_radio_static('photo_title',str('BROWSE_PHOTO_TITLE'),$option_vals,get_sys_pref('browse_photo_title_enabled','YES'),false,true);
    form_radio_static('photo_filesystem',str('BROWSE_FILESYSTEM'),$option_vals,get_sys_pref('browse_photo_filesystem_enabled','YES'),false,true);
    form_submit(str('SAVE_SETTINGS'), 2);
    form_end();
  }
  
  // ----------------------------------------------------------------------------------
  // Saves the new parameters
  // ----------------------------------------------------------------------------------
  
  function browse_set_opts()
  {
    $failed = false;
    
    if ( $_REQUEST["video_title"] == 'YES' || $_REQUEST["video_actor"] == 'YES' || $_REQUEST["video_director"] == 'YES' || 
         $_REQUEST["video_genre"] == 'YES' ||  $_REQUEST["video_year"] == 'YES' || $_REQUEST["video_certificate"] == 'YES' || $_REQUEST["video_filesystem"] == 'YES' )
    {
      set_sys_pref('browse_video_title_enabled',$_REQUEST["video_title"]);
      set_sys_pref('browse_video_actor_enabled',$_REQUEST["video_actor"]);
      set_sys_pref('browse_video_director_enabled',$_REQUEST["video_director"]);
      set_sys_pref('browse_video_genre_enabled',$_REQUEST["video_genre"]);
      set_sys_pref('browse_video_year_enabled',$_REQUEST["video_year"]);
      set_sys_pref('browse_video_certificate_enabled',$_REQUEST["video_certificate"]);
      set_sys_pref('browse_video_filesystem_enabled',$_REQUEST["video_filesystem"]);
    }
    else
      $failed = true;
    
    if ( $_REQUEST["music_artist"] == 'YES' || $_REQUEST["music_album"] == 'YES' || $_REQUEST["music_track"] == 'YES' || 
         $_REQUEST["music_genre"] == 'YES' || $_REQUEST["music_year"] == 'YES' || $_REQUEST["music_filesystem"] == 'YES' )
    {
      set_sys_pref('browse_music_artist_enabled',$_REQUEST["music_artist"]);
      set_sys_pref('browse_music_album_enabled',$_REQUEST["music_album"]);
      set_sys_pref('browse_music_track_enabled',$_REQUEST["music_track"]);
      set_sys_pref('browse_music_genre_enabled',$_REQUEST["music_genre"]);
      set_sys_pref('browse_music_year_enabled',$_REQUEST["music_year"]);
      set_sys_pref('browse_music_filesystem_enabled',$_REQUEST["music_filesystem"]);
    }
    else
      $failed = true;
      
    if ( $_REQUEST["photo_album"] == 'YES' || $_REQUEST["photo_title"] == 'YES' || $_REQUEST["photo_filesystem"] == 'YES' )
    {
      set_sys_pref('browse_photo_album_enabled',$_REQUEST["photo_album"]);
      set_sys_pref('browse_photo_title_enabled',$_REQUEST["photo_title"]);
      set_sys_pref('browse_photo_filesystem_enabled',$_REQUEST["photo_filesystem"]);
    } 
    else
      $failed = true;
      
    if ($failed == true)
      browse_display("!".str('CONFIG_BROWSE_NONE_SELECTED'));
    else
      browse_display(str('SAVE_SETTINGS_OK'));
  }
  
/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>