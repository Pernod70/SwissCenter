<?php
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor
 *************************************************************************************************/

  require_once( realpath(dirname(__FILE__).'/base/page.php'));
  require_once( realpath(dirname(__FILE__).'/base/mysql.php'));
  require_once( realpath(dirname(__FILE__).'/base/file.php'));
  require_once( realpath(dirname(__FILE__).'/base/playlist.php'));
  require_once( realpath(dirname(__FILE__).'/base/rating.php'));
  require_once( realpath(dirname(__FILE__).'/base/search.php'));
  require_once( realpath(dirname(__FILE__).'/ext/exif/exif_reader.php'));

  //*************************************************************************************************
  // Build page elements
  //*************************************************************************************************

  $menu       = new menu();
  $info       = new infotab();
  $sql_table_all = 'photos media '.
                   'left outer join photo_albums pa on media.dirname = pa.dirname '.
                   get_rating_join().' where 1=1 ';
  $predicate  = search_process_passed_params();
  $sql_table  = 'photos media ';
  // Only join tables that are actually required
  if (strpos($predicate,'title like') > 0)
    $sql_table .= 'left outer join photo_albums pa on media.dirname = pa.dirname ';
  $sql_table .= get_rating_join().' where 1=1 ';
  $count      = db_value("select count(distinct media.file_id) from $sql_table $predicate");
  $refine_url = 'photo_search.php';
  $this_url   = url_set_param(current_url(),'add','N');
  $play_order = get_user_pref('PHOTO_PLAY_ORDER','filename');
  $delay      = get_user_pref('PHOTO_PLAY_TIME',5);
  $music      = isset($_SESSION["background_music"]) ? $_SESSION["background_music"] : '*'; // default to the current playlist

  // What do we output to the user when it comes to describing the currently selected background music?
  if ($music == '')
    $music_txt = str('PHOTOS_MUSIC_NONE');
  elseif ($music='*')
    $music_txt = str('PHOTOS_MUSIC_CURRENT');
  else
    $music_txt = $music;

  // Work out what to display
  if ($count == 1)
  {
    $pic   = array_pop(db_toarray("select * from $sql_table $predicate"));
    $flash = explode(',',exif_val('Flash',$pic['EXIF_FLASH']));

    // Stop the make from appearing twice (such as "Canon Canon EOS 10D").
    if (!empty($pic['EXIF_MAKE']) && strpos( strtolower($pic['EXIF_MODEL']),strtolower($pic['EXIF_MAKE'])) !== false)
      $info->add_item(str('EXIF_MODEL')          ,$pic['EXIF_MODEL']);
    else
      $info->add_item(str('EXIF_MODEL')          ,$pic['EXIF_MAKE'].' '.$pic['EXIF_MODEL']);

    // Exposure details
    $info->add_item(str('EXIF_EXPOSURE')       ,sprintf('%s - f%s - %s', $pic['EXIF_EXPOSURE_TIME']
                                                                       , $pic['EXIF_FNUMBER']
                                                                       , $pic['EXIF_FOCAL_LENGTH']));

    $info->add_item(str('EXIF_ISO')            ,$pic['EXIF_ISO']);
    $info->add_item(str('EXIF_WHITE_BALANCE')  ,exif_val('WhiteBalance',$pic['EXIF_WHITE_BALANCE']));
    $info->add_item(str('EXIF_LIGHT_SOURCE')   ,exif_val('LightSource',$pic['EXIF_LIGHT_SOURCE']));
    $info->add_item(str('EXIF_EXPOSE_PROG')    ,exif_val('ExpProg',$pic['EXIF_EXPOSURE_PROG']));
    $info->add_item(str('EXIF_METER_MODE')     ,exif_val('MeterMode',$pic['EXIF_METER_MODE']));
    $info->add_item(str('EXIF_SCENCE_CAPTURE') ,exif_val('SceneCaptureType',$pic['EXIF_CAPTURE_TYPE']));
    $info->add_item(str('EXIF_FLASH')          ,$flash[0]);

    $menu->add_item(str('START_SLIDESHOW'), play_sql_list(MEDIA_TYPE_PHOTO,"select media.* from $sql_table $predicate order by $play_order") );

    // TO-DO
    // Expand to album
  }
  else
  {
    // Information on the current selection  - no point showing the no. of slides or timing info for one slide though!
    $info->add_item(str('PHOTOS_NO_SELECTED')  , $count);
    $info->add_item(str('PHOTOS_TIME_ONE')     , $delay.' Seconds');
    $info->add_item(str('PHOTOS_TIME_ALL')     , hhmmss($delay * $count));
    $info->add_item(str('PHOTOS_MUSIC_INFO')   , $music_txt);

    switch ($play_order)
    {
      case 'filename'      : $info->add_item(str('PHOTO_PLAY_ORDER')   , str('PHOTO_ORDER_NAME') ); break;
      case 'date_created'  : $info->add_item(str('PHOTO_PLAY_ORDER')   , str('PHOTO_ORDER_DATE_TAKEN') ); break;
      case 'date_modified' : $info->add_item(str('PHOTO_PLAY_ORDER')   , str('PHOTO_ORDER_DATE_DISK') ); break;
      default              : $info->add_item(str('PHOTO_PLAY_ORDER')   , str('PHOTO_ORDER_DATE_RANDOM') ); break;
    }

    $menu->add_item(str('START_SLIDESHOW'), play_sql_list(MEDIA_TYPE_PHOTO,"select media.* from $sql_table $predicate order by $play_order") );
    search_check_filter( $menu, str('REFINE_PHOTO_ALBUM'),  'title',  $sql_table_all, $predicate, $refine_url );
    search_check_filter( $menu, str('REFINE_PHOTO_TITLE'),  'filename',  $sql_table, $predicate, $refine_url );
    search_check_filter( $menu, str('REFINE_IPTC_BYLINE'),  'iptc_byline',  $sql_table, $predicate, $refine_url );
    search_check_filter( $menu, str('REFINE_IPTC_CAPTION'), 'iptc_caption',  $sql_table, $predicate, $refine_url );
    search_check_filter( $menu, str('REFINE_IPTC_LOCATION'),'iptc_location',  $sql_table, $predicate, $refine_url );
    search_check_filter( $menu, str('REFINE_IPTC_CITY'),    'iptc_city',  $sql_table, $predicate, $refine_url );
    search_check_filter( $menu, str('REFINE_IPTC_PROVINCE_STATE'),  'iptc_province_state',  $sql_table, $predicate, $refine_url );
    search_check_filter( $menu, str('REFINE_IPTC_COUNTRY'), 'iptc_country',  $sql_table, $predicate, $refine_url );
//    search_check_filter( $menu, str('REFINE_IPTC_KEYWORDS'),'iptc_keywords',  $sql_table, $predicate, $refine_url );
//    search_check_filter( $menu, str('REFINE_IPTC_SUPPCATEGORY'),    'iptc_suppcategory',  $sql_table, $predicate, $refine_url );
    search_check_filter( $menu, str('REFINE_XMP_RATING'),   'xmp_rating',  $sql_table, $predicate, $refine_url );

    // TO-DO
    // Expand to parent album
  }

    $menu->add_item(str('PHOTOS_MUSIC_CHANGE'), 'photo_change_music.php', true);

  if ($count >1)
  {
    $menu->add_item( str('PHOTO_CHANGE_TIME'), 'photo_change_time.php', true);
    $menu->add_item( str('PHOTO_CHANGE_ORDER'), 'photo_change_order.php', true);
  }

  $folder_img = file_albumart( db_value("select concat(media.dirname,media.filename) from $sql_table $predicate order by media.$play_order limit 0,1") );

  // Display Page
  page_header( str('SLIDESHOW'), '', '<meta SYABAS-PLAYERMODE="photo">' );
  if (! empty($folder_img) )
  {
    $info->display();
    echo '<p><table width="100%" cellpadding=0 cellspacing=0 border=0>
          <tr><td valign=top width="'.convert_x(290).'" align="center">
              <table width="100%"><tr><td height="'.convert_y(10).'"></td></tr><tr><td valign=top>
                <center>'.img_gen($folder_img,250,300).'</center>
              </td></tr></table></td>
              <td valign="top">';
              $menu->display(1, 480);
    echo '    </td></td></table>';
  }
  else
  {
    $info->display();
    $menu->display();
  }

  // Display ABC buttons
  $buttons = array();
  page_footer( url_add_params( search_picker_most_recent(), array("p_del"=>"y","del"=>"y") ), $buttons );

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
