<?
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor
 *************************************************************************************************/

  require_once("base/page.php");

  page_header("View Photographs",'', 'LOGO_PHOTO');

  echo '<center>Please select an option from the list:</center><p>';

  $menu = new menu();
  $menu->add_item("Browse Filesystem","photo_browse.php",true);
  $menu->display();
  page_footer( 'index.php' );

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
