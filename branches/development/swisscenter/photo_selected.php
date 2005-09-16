<?
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor
 *************************************************************************************************/

  require_once("base/page.php");
  require_once("base/mysql.php");
  require_once("base/file.php");
  require_once("base/playlist.php");
  require_once("base/rating.php");
  require_once("base/search.php");
  
  //*************************************************************************************************
  // Build page elements
  //*************************************************************************************************

  $menu       = new menu();
  $info       = new infotab();
  $delay      = 5;
  $sql_table  = "photos media ".get_rating_join()." inner join photo_albums pa on media.dirname like concat(pa.dirname,'%') where 1=1 ";
  $predicate  = search_process_passed_params();
  $count      = db_value("select count(distinct media.file_id) from $sql_table $predicate");
  $refine_url = 'photo_search.php';
  $this_url   = url_set_param(current_url(),'add','N');
  

  if ($count == 1)
  {
    $pic   = array_pop(db_toarray("select * from $sql_table $predicate"));
    $flash = explode(',',$pic['EXIF_FLASH']);
    
    $info->add_item(str('EXIF_EXPOSE_MODE')    ,$pic['EXIF_EXPOSURE_MODE']);
    $info->add_item(str('EXIF_EXPOSE_TIME')    ,$pic['EXIF_EXPOSURE_TIME']);
    $info->add_item(str('EXIF_FNUMBER')        ,rtrim($pic['EXIF_FNUMBER'],'0'));
    $info->add_item(str('EXIF_FOCAL_LENGTH')   ,$pic['EXIF_FOCAL_LENGTH']);
    $info->add_item(str('EXIF_SOURCE')         ,$pic['EXIF_IMAGE_SOURCE']);
    $info->add_item(str('EXIF_MAKE')           ,$pic['EXIF_MAKE']);
    $info->add_item(str('EXIF_MODEL')          ,$pic['EXIF_MODEL']);
    $info->add_item(str('EXIF_ORIENTATION')    ,$pic['EXIF_ORIENTATION']);
    $info->add_item(str('EXIF_WHITE_BALANCE')  ,$pic['EXIF_WHITE_BALANCE']);
    $info->add_item(str('EXIF_FLASH')          ,$flash[0]);
    $info->add_item(str('EXIF_ISO')            ,$pic['EXIF_ISO']);
    $info->add_item(str('EXIF_LIGHT_SOURCE')   ,$pic['EXIF_LIGHT_SOURCE']);
    $info->add_item(str('EXIF_EXPOSE_PROG')    ,$pic['EXIF_EXPOSURE_PROG']);
    $info->add_item(str('EXIF_METER_MODE')     ,$pic['EXIF_METER_MODE']);
    $info->add_item(str('EXIF_SCENCE_CAPTURE') ,$pic['EXIF_CAPTURE_TYPE']);
  }
  else 
  {
    // Information on the current selection  - no point showing the no. of slides or timing info for one slide though!
    $info->add_item(str('PHOTOS_NO_SELECTED')  , $count);
    $info->add_item(str('PHOTOS_TIME_ONE')     , $delay.' Seconds');
    $info->add_item(str('PHOTOS_TIME_ALL')     , hhmmss($delay * $count));
  }
  
  // Menu Options
  search_check_filter( $menu, str('REFINE_PHOTO_ALBUM'),  'title',  $sql_table, $predicate, $refine_url );
  search_check_filter( $menu, str('REFINE_PHOTO_TITLE'),  'filename',  $sql_table, $predicate, $refine_url );
  $menu->add_item(str('START_SLIDESHOW'), play_sql_list(MEDIA_TYPE_PHOTO,"select media.* from $sql_table $predicate order by title") );

  // Display Page
  page_header(str('SLIDESHOW'),'');
  $info->display();
  $menu->display();

  // Display ABC buttons
  if (!isset($_SESSION["shuffle"]) || $_SESSION["shuffle"] == 'off')
    $buttons[] = array('text'=>str('SHUFFLE_ON'), 'url'=> url_set_param($this_url,'shuffle','on') );
  else
    $buttons[] = array('text'=>str('SHUFFLE_OFF'), 'url'=> url_set_param($this_url,'shuffle','off') );

  page_footer( url_add_param($_SESSION["last_picker"][count($_SESSION["history"])-1],'del','y'), $buttons );

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
