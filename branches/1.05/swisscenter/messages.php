<?
//*************************************************************************************************
//  SWISScenter Source                                                              Robert Taylor
//*************************************************************************************************

  require_once("base/page.php");

  function delete_message($delete_id)
  {
    // Delete the message from the database
    $success = db_sqlcommand("UPDATE messages SET deleted=1 WHERE message_id=".$delete_id);
  
    // Display the message deleted page
    page_header('Messages');

    if($success)
    {
      echo '<br><center><font color="'.$_SESSION["opts"]["style"]["PAGE_TEXT"].
            '">The selected message was successfully deleted</font></center></br>';
    }
    else
    {
      echo '<br><center><font color="'.$_SESSION["opts"]["style"]["PAGE_TEXT"].
            '">There was an error deleting the selected message</font></center></br>';
    }
    
    $menu = new menu();
    $menu->add_item('Continue', 'messages.php');
    $menu->display();
  }
  
  function list_messages()
  {
    page_header( "Messages" );

    $num = db_value("select count(*) from messages where deleted is null");

    // If there is only one unread message, then don't bother with the menu.
    if ($num == 1)
      $id = db_value("select message_id from messages where deleted is null");
    else 
      $id = $_REQUEST["id"];

    // One item, or the user has selected an item?
    if (!empty($id))
    {
      $menu = new menu();
      $data = array_pop(db_toarray("select * from messages where message_id=".$id));

      echo '<center><font color="'.$_SESSION["opts"]["style"]["TITLE_COLOUR"].'">
            '.$data["TITLE"].'</font></center><p>'.$data["MESSAGE_TEXT"].'<p>';

      if($num > 1)
      {
        // More than one message, go back to the list of messages
        $menu->add_item('Keep this message','messages.php');
      }
      else
      {
        // Only the one message, go back to the previous screen
        $menu->add_item('Keep this message','config.php');
      }

      $menu->add_item('Delete this message','messages.php?delete='.$id);
      $menu->display();
    }
    else
    {
      // Display a list of all the outstanding messages
      $data = db_toarray("select * from messages where deleted is null order by added");
      $menu = new menu();
      foreach ($data as $row)
        $menu->add_item($row["TITLE"],'messages.php?id='.$row["MESSAGE_ID"]);
      $menu->display();
    }
  }

//*************************************************************************************************
// Main Code
//*************************************************************************************************



  if(!empty($_REQUEST["delete"]))
  {
    delete_message($_REQUEST["delete"]);
  }
  else
  {
    list_messages();
  }
   
  page_footer( 'config.php' );

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
