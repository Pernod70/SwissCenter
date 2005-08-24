<?
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor
 *************************************************************************************************/

  require_once("base/page.php");
  require_once("messages_db.php");
  
  page_header( str('SETUP_TITLE'), '', 'LOGO_CONFIG' );

  echo '<p align="center">'.str('SELECT_OPTION');
  $menu = new menu();

  // Are there any new messages to display to the user?
  $num_read = count_messages_with_status(MESSAGE_STATUS_READ);
  $num_new = count_messages_with_status(MESSAGE_STATUS_NEW);
  if(($num_read + $num_new) > 0)
  {
    $menu->add_item(str('MESSAGES_VIEW')
                   ." (".$num_new." ".str('MESSAGE_STATUS_NEW').", ".$num_read." ".str('MESSAGE_STATUS_READ').")"
                   ,'messages.php?return='.current_url(),true);
  }

  $menu->add_item(str('SETUP_CHANGE_UI'),'style.php',true);
  $menu->add_item(str('SETUP_SEARCH_NEW_MEDIA'),'do_refresh.php');
  
  // Does the User have internet connectivity?
  if (internet_available())
    $menu->add_item(str('SETUP_UPDATE_SC'),'run_update.php');  

  $menu->add_item(str('PIN_CHANGE'), 'change_pin.php');

  $menu->display();
  page_footer( 'index.php' );

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
