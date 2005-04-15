<?
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor
 *************************************************************************************************/

  require_once("base/page.php");
  require_once("messages_db.php");
  
  page_header( "Preferences and Setup", '', 'LOGO_CONFIG' );

  echo '<p align="center">Please select an option from the list:';
  $menu = new menu();

  // Are there any new messages to display to the user?
  $num_read = count_messages_with_status(MESSAGE_STATUS_READ);
  $num_new = count_messages_with_status(MESSAGE_STATUS_NEW);
  if(($num_read + $num_new) > 0)
  {
    $menu->add_item("View Messages (".$num_new." new, ".$num_read." read)",'messages.php?return='.current_url(),true);
  }

  $menu->add_item("Change User Interface Style",'style.php',true);
  $menu->add_item("Search For New Media",'do_refresh.php');
  
  // Does the User have internet connectivity?
  if (internet_available())
    $menu->add_item("Update SwissCenter",'run_update.php');  

  $menu->display();
  page_footer( 'index.php' );

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
