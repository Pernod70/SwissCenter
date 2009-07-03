<?php
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
    form_radio_static('video_rating',str('BROWSE_RATING'),$option_vals,get_sys_pref('browse_video_rating_enabled','YES'),false,true);
    form_radio_static('video_discovered',str('BROWSE_DISCOVERED'),$option_vals,get_sys_pref('browse_video_discovered_enabled','YES'),false,true);
    form_radio_static('video_timestamp',str('BROWSE_TIMESTAMP'),$option_vals,get_sys_pref('browse_video_timestamp_enabled','YES'),false,true);
    form_radio_static('video_filesystem',str('BROWSE_FILESYSTEM'),$option_vals,get_sys_pref('browse_video_filesystem_enabled','YES'),false,true);

    form_label('<p>&nbsp;<br><b>'.str('BROWSE_MUSIC').'</b>','L');
    form_radio_static('music_artist',str('BROWSE_ARTIST'),$option_vals,get_sys_pref('browse_music_artist_enabled','YES'),false,true);
    form_radio_static('music_album_artist',str('BROWSE_ALBUM_ARTIST'),$option_vals,get_sys_pref('browse_music_album_artist_enabled','YES'),false,true);
    form_radio_static('music_album',str('BROWSE_ALBUM'),$option_vals,get_sys_pref('browse_music_album_enabled','YES'),false,true);
    form_radio_static('music_track',str('BROWSE_TRACK'),$option_vals,get_sys_pref('browse_music_track_enabled','YES'),false,true);
    form_radio_static('music_genre',str('BROWSE_GENRE'),$option_vals,get_sys_pref('browse_music_genre_enabled','YES'),false,true);
    form_radio_static('music_year',str('BROWSE_YEAR'),$option_vals,get_sys_pref('browse_music_year_enabled','YES'),false,true);
    form_radio_static('music_discovered',str('BROWSE_DISCOVERED'),$option_vals,get_sys_pref('browse_music_discovered_enabled','YES'),false,true);
    form_radio_static('music_timestamp',str('BROWSE_TIMESTAMP'),$option_vals,get_sys_pref('browse_music_timestamp_enabled','YES'),false,true);
    form_radio_static('music_filesystem',str('BROWSE_FILESYSTEM'),$option_vals,get_sys_pref('browse_music_filesystem_enabled','YES'),false,true);

    form_label('<p>&nbsp;<br><b>'.str('BROWSE_PHOTOS').'</b>','L');
    form_radio_static('photo_album',str('BROWSE_PHOTO_ALBUM'),$option_vals,get_sys_pref('browse_photo_album_enabled','YES'),false,true);
    form_radio_static('photo_title',str('BROWSE_PHOTO_TITLE'),$option_vals,get_sys_pref('browse_photo_title_enabled','YES'),false,true);
    form_radio_static('photo_author',str('BROWSE_IPTC_BYLINE'),$option_vals,get_sys_pref('browse_iptc_byline_enabled','YES'),false,true);
//    form_radio_static('photo_caption',str('BROWSE_IPTC_CAPTION'),$option_vals,get_sys_pref('browse_iptc_caption_enabled','YES'),false,true);
    form_radio_static('photo_location',str('BROWSE_IPTC_LOCATION'),$option_vals,get_sys_pref('browse_iptc_location_enabled','YES'),false,true);
    form_radio_static('photo_city',str('BROWSE_IPTC_CITY'),$option_vals,get_sys_pref('browse_iptc_city_enabled','YES'),false,true);
    form_radio_static('photo_state',str('BROWSE_IPTC_PROVINCE_STATE'),$option_vals,get_sys_pref('browse_iptc_province_state_enabled','YES'),false,true);
    form_radio_static('photo_country',str('BROWSE_IPTC_COUNTRY'),$option_vals,get_sys_pref('browse_iptc_country_enabled','YES'),false,true);
//    form_radio_static('photo_keywords',str('BROWSE_IPTC_KEYWORDS'),$option_vals,get_sys_pref('browse_iptc_keywords_enabled','YES'),false,true);
//    form_radio_static('photo_category',str('BROWSE_IPTC_SUPPCATEGORY'),$option_vals,get_sys_pref('browse_iptc_suppcategory_enabled','YES'),false,true);
    form_radio_static('photo_rating',str('BROWSE_XMP_RATING'),$option_vals,get_sys_pref('browse_xmp_rating_enabled','YES'),false,true);
    form_radio_static('photo_discovered',str('BROWSE_DISCOVERED'),$option_vals,get_sys_pref('browse_photo_discovered_enabled','YES'),false,true);
    form_radio_static('photo_timestamp',str('BROWSE_TIMESTAMP'),$option_vals,get_sys_pref('browse_photo_timestamp_enabled','YES'),false,true);
    form_radio_static('photo_filesystem',str('BROWSE_FILESYSTEM'),$option_vals,get_sys_pref('browse_photo_filesystem_enabled','YES'),false,true);

    echo '<tr><td></td><td>&nbsp;<br>'.form_submit_html(str('SAVE_SETTINGS')).'</td></tr>';
    form_end();
  }

  // ----------------------------------------------------------------------------------
  // Saves the new parameters
  // ----------------------------------------------------------------------------------

  function browse_set_opts()
  {
    $failed = false;

    if ( $_REQUEST["video_title"] == 'YES' || $_REQUEST["video_actor"] == 'YES' || $_REQUEST["video_director"] == 'YES' ||
         $_REQUEST["video_genre"] == 'YES' ||  $_REQUEST["video_year"] == 'YES' || $_REQUEST["video_certificate"] == 'YES' ||
         $_REQUEST["video_rating"] == 'YES' || $_REQUEST["video_discovered"] == 'YES' || $_REQUEST["video_timestamp"] == 'YES' ||
         $_REQUEST["video_filesystem"] == 'YES' )
    {
      set_sys_pref('browse_video_title_enabled',$_REQUEST["video_title"]);
      set_sys_pref('browse_video_actor_enabled',$_REQUEST["video_actor"]);
      set_sys_pref('browse_video_director_enabled',$_REQUEST["video_director"]);
      set_sys_pref('browse_video_genre_enabled',$_REQUEST["video_genre"]);
      set_sys_pref('browse_video_year_enabled',$_REQUEST["video_year"]);
      set_sys_pref('browse_video_certificate_enabled',$_REQUEST["video_certificate"]);
      set_sys_pref('browse_video_rating_enabled',$_REQUEST["video_rating"]);
      set_sys_pref('browse_video_discovered_enabled',$_REQUEST["video_discovered"]);
      set_sys_pref('browse_video_timestamp_enabled',$_REQUEST["video_timestamp"]);
      set_sys_pref('browse_video_filesystem_enabled',$_REQUEST["video_filesystem"]);
    }
    else
      $failed = true;

    if ( $_REQUEST["music_artist"] == 'YES' || $_REQUEST["music_album"] == 'YES' || $_REQUEST["music_track"] == 'YES' ||
         $_REQUEST["music_genre"] == 'YES' || $_REQUEST["music_year"] == 'YES' || $_REQUEST["music_filesystem"] == 'YES' ||
         $_REQUEST["music_discovered"] == 'YES' || $_REQUEST["music_timestamp"] == 'YES' || $_REQUEST["music_album_artist"] == 'YES' )
    {
      set_sys_pref('browse_music_artist_enabled',$_REQUEST["music_artist"]);
      set_sys_pref('browse_music_album_artist_enabled',$_REQUEST["music_album_artist"]);
      set_sys_pref('browse_music_album_enabled',$_REQUEST["music_album"]);
      set_sys_pref('browse_music_track_enabled',$_REQUEST["music_track"]);
      set_sys_pref('browse_music_genre_enabled',$_REQUEST["music_genre"]);
      set_sys_pref('browse_music_year_enabled',$_REQUEST["music_year"]);
      set_sys_pref('browse_music_discovered_enabled',$_REQUEST["music_discovered"]);
      set_sys_pref('browse_music_timestamp_enabled',$_REQUEST["music_timestamp"]);
      set_sys_pref('browse_music_filesystem_enabled',$_REQUEST["music_filesystem"]);
    }
    else
      $failed = true;

    if ( $_REQUEST["photo_album"] == 'YES' || $_REQUEST["photo_title"] == 'YES' ||
         $_REQUEST["photo_author"] == 'YES' || $_REQUEST["photo_caption"] == 'YES' ||
         $_REQUEST["photo_city"] == 'YES' || $_REQUEST["photo_country"] == 'YES' ||
         $_REQUEST["photo_keywords"] == 'YES' || $_REQUEST["photo_location"] == 'YES' ||
         $_REQUEST["photo_state"] == 'YES' || $_REQUEST["photo_category"] == 'YES' ||
         $_REQUEST["photo_rating"] == 'YES' || $_REQUEST["photo_discovered"] == 'YES' ||
         $_REQUEST["photo_timestamp"] == 'YES' ||$_REQUEST["photo_filesystem"] == 'YES' )
    {
      set_sys_pref('browse_photo_album_enabled',$_REQUEST["photo_album"]);
      set_sys_pref('browse_photo_title_enabled',$_REQUEST["photo_title"]);
      set_sys_pref('browse_iptc_byline_enabled',$_REQUEST["photo_author"]);
//      set_sys_pref('browse_iptc_caption_enabled',$_REQUEST["photo_caption"]);
      set_sys_pref('browse_iptc_city_enabled',$_REQUEST["photo_city"]);
      set_sys_pref('browse_iptc_country_enabled',$_REQUEST["photo_country"]);
//      set_sys_pref('browse_iptc_keywords_enabled',$_REQUEST["photo_keywords"]);
      set_sys_pref('browse_iptc_location_enabled',$_REQUEST["photo_location"]);
      set_sys_pref('browse_iptc_province_state_enabled',$_REQUEST["photo_state"]);
//      set_sys_pref('browse_iptc_suppcategory_enabled',$_REQUEST["photo_category"]);
      set_sys_pref('browse_xmp_rating_enabled',$_REQUEST["photo_rating"]);
      set_sys_pref('browse_photo_discovered_enabled',$_REQUEST["photo_discovered"]);
      set_sys_pref('browse_photo_timestamp_enabled',$_REQUEST["photo_timestamp"]);
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
