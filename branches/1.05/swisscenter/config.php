<?
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor
 *************************************************************************************************/

  require_once("base/page.php");

  page_header( "Preferences and Setup" );

  echo '<p>Please select an option from the list:';

  $menu = new menu();
  $menu->add_item("Change User Interface Style",'style.php',true);
  $menu->add_item("Search for new media",'do_refresh.php');

  // Does the User have internet connectivity?
  if ($_SESSION["internet"])
  {
    $menu->add_item("Update SwissCenter",'run_update.php');  
  }

  // Are there any new messages to display to the user?
  $num = db_value("select count(*) from messages where deleted is null");
  if ($num >0)
  {
    $menu->add_item("View Messages (".$num.")",'messages.php',true);
  }
   
  $menu->display();

  page_footer( 'index.php' );

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
