<?
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor
 *************************************************************************************************/

  require_once("base/page.php");

  page_header( "Refresh Media Databases" );

  echo '<p>Please select an option from the list:';

  $menu = new menu();
  $menu->add_item("Search for new media",'do_refresh.php?type=all');
  $menu->add_item("Search for new movies only",'do_refresh.php?type=video');
  $menu->add_item("Search for new music only",'do_refresh.php?type=music');
//  $menu->add_item("Refresh Photo Database",'do_refresh.php?type=photo');
  $menu->add_item("Rebuild entire database",'setup_db.php');
  $menu->display();

  page_footer( 'config.php' );

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
