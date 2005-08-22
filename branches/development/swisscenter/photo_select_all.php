<?
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor
 *************************************************************************************************/

  require_once("base/page.php");
  require_once("base/playlist.php");
  require_once("base/infotab.php");
  require_once("base/rating.php");
  require_once("base/categories.php");
  
  $post_sql = $sql.$_SESSION["history"][0]["sql"];
  $dir   = un_magic_quote(rawurldecode($_REQUEST["dir"]));
  $spec  = "select * from photos media ".get_rating_join()."where dirname like '<<Photo>>/$dir%'".$post_sql;
  $data  = pl_tracklist('dir', $spec);
  $count = count($data);
  $info  = new infotab();
  $menu  = new menu();
  $delay = 5;


  // Information on the current selection  
  $info->add_item(str('PHOTOS_NO_SELECTED'), $count);
  $info->add_item(str('PHOTOS_TIME_ONE'), $delay.' Seconds');
  $info->add_item(str('PHOTOS_TIME_ALL'), hhmmss($delay * $count));

  // Menu Options
  $menu->add_item(str('START_SLIDESHOW'),pl_link('dir',$spec,'photo'));

  // Display Page
  page_header(str('SLIDESHOW'),'/'.$dir,'LOGO_PHOTO');
  $info->display();
  $menu->display();
  page_footer( 'photo_browse.php?DIR='.$_REQUEST["dir"] );


/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
