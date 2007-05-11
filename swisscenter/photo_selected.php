<?
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
  $sql_table  = "photos media ".get_rating_join()." left outer join photo_albums pa on media.dirname like concat(pa.dirname,'%') where 1=1 ";
  $predicate  = search_process_passed_params();
  $count      = db_value("select count(distinct media.file_id) from $sql_table $predicate");
  $refine_url = 'photo_search.php';
  $this_url   = url_set_param(current_url(),'add','N');
  $play_order = get_user_pref('PHOTO_PLAY_ORDER','filename');
  $delay      = get_user_pref('PHOTO_PLAY_TIME',5);

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

    switch ($play_order)
    {
      case 'filename'      : $info->add_item(str('PHOTO_PLAY_ORDER')   , str('PHOTO_ORDER_NAME') ); break;
      case 'date_created'  : $info->add_item(str('PHOTO_PLAY_ORDER')   , str('PHOTO_ORDER_DATE_TAKEN') ); break;
      case 'date_modified' : $info->add_item(str('PHOTO_PLAY_ORDER')   , str('PHOTO_ORDER_DATE_DISK') ); break;
      default              : $info->add_item(str('PHOTO_PLAY_ORDER')   , str('PHOTO_ORDER_DATE_RANDOM') ); break;
    }

    $menu->add_item(str('START_SLIDESHOW'), play_sql_list(MEDIA_TYPE_PHOTO,"select media.* from $sql_table $predicate order by $play_order") );
    search_check_filter( $menu, str('REFINE_PHOTO_ALBUM'),  'title',  $sql_table, $predicate, $refine_url );
    search_check_filter( $menu, str('REFINE_PHOTO_TITLE'),  'filename',  $sql_table, $predicate, $refine_url );

    // TO-DO
    // Expand to parent album
  }

  if ($count >1)
  {
    $menu->add_item( str('PHOTO_CHANGE_ORDER'), 'photo_change_order.php', true);
    $menu->add_item( str('PHOTO_CHANGE_TIME'), 'photo_change_time.php', true);
  }

  // Display Page
  page_header(str('SLIDESHOW'),'');
  $info->display();
  $menu->display();

  // Display ABC buttons
  page_footer( url_add_param($_SESSION["last_picker"][count($_SESSION["history"])-1],'del','y'), $buttons );

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
