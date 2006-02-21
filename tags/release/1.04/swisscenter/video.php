<?
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor
 *************************************************************************************************/

  require_once("base/page.php");

  page_header("Watch A Movie");

  echo '<center>Please select an option from the list:</center><p>';

  $menu = new menu();
  $menu->add_item("Browse","video_browse.php",true);
//  $menu->add_item("Search by Title","",true);
//  $menu->add_item("Search by Collection","",true);
  $menu->display();
  page_footer( 'index.php' );

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
