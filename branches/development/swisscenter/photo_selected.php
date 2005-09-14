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
  
  // Information on the current selection  
  $info->add_item(str('PHOTOS_NO_SELECTED'), $count);
  $info->add_item(str('PHOTOS_TIME_ONE'), $delay.' Seconds');
  $info->add_item(str('PHOTOS_TIME_ALL'), hhmmss($delay * $count));

  // Menu Options
  search_check_filter( $menu, str('REFINE_PHOTO_ALBUM'),  'title',  $sql_table, $predicate, $refine_url );
  search_check_filter( $menu, str('REFINE_PHOTO_TITLE'),  'filename',  $sql_table, $predicate, $refine_url );
  $menu->add_item(str('START_SLIDESHOW'),pl_link('sql',"select media.* from $sql_table $predicate order by title",'photo'));

  // Display Page
  page_header(str('SLIDESHOW'),'','LOGO_PHOTO');
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
