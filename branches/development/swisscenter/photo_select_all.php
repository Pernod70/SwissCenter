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
  $info->add_item('No. Photos', $count);
  $info->add_item('Time Per Photo', $delay.' Seconds');
  $info->add_item('Total Play Time', hhmmss($delay * $count));

  // Menu Options
  $menu->add_item("Start Slideshow",pl_link('dir',$spec,'photo'));

  // Display Page
  page_header("Slideshow",'/'.$dir,'LOGO_PHOTO');
  $info->display();
  $menu->display();
  page_footer( 'photo_browse.php?DIR='.$_REQUEST["dir"] );


/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
