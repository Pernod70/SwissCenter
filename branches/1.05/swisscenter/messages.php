<?
//*************************************************************************************************
//  SWISScenter Source                                                              Robert Taylor
//*************************************************************************************************

  require_once("base/page.php");
  require_once("messages_db.php");

  function delete_message($delete_id)
  {
    // Delete the message from the database
    $success = db_sqlcommand("UPDATE messages SET status=".MESSAGE_STATUS_DELETED." WHERE message_id=".$delete_id);
  }
  
  function list_messages()
  {
    // Display a message if there are no messages to display, otherwise list them
    // Get the number of new or read messages
    $num = count_new_and_unread_messages();

    if($num == 0)
    {
      page_header( "Messages", "", "1", '<meta http-equiv="refresh" content="2;URL=config.php">');
      
      // No messages to display
      echo '<center><font color="'.$_SESSION["opts"]["style"]["PAGE_TEXT"].
            '">There are no messages to display</font></center></br>';
    }
    else
    {
      page_header( "Messages" );
      // Display a list of all the outstanding messages
      echo '<center><font color="'.$_SESSION["opts"]["style"]["PAGE_TEXT"].
            '">Please select a message to view</font></center></br>';

      // Find out what page we are on, default to page 0 (first page)      
      $page = $_REQUEST["page"];
      if(empty($page))
        $page = 0;
      
      // Get the data from the database for the current page
      $data = get_new_and_unread_messages();

      // List all of the messages in a menu
      $menu = new menu();
      foreach ($data as $row)
      {
        if($row["STATUS"] == MESSAGE_STATUS_NEW)
          $message_text = "(New) ".$row["TITLE"];
        else
          $message_text = $row["TITLE"];
        
        // Calculate the page that the user should be returned to if they choose to delete
        // this message. Should return to the current page unless this is the last item on
        // the page in which case they'll go back to the previous page unless this is page 0
        // in which case they'll always go back to page 0
        if((count($data) > 1) || ($page == 0))
          $pagedel = $page;
        else if($page > 0)
          $pagedel = $page - 1;

        // Add a link to display the message, this includes the pages that should be displayed
        // if the keep or delete options are chosen
        $menu->add_item($message_text,'messages.php?id='.$row["MESSAGE_ID"].'&pagekeep='.$page
              .'&pagedel='.$pagedel);
      }

      // Add up/down buttons as needed for prev/next page      
      if ($page > 0)
        $menu->add_up( $this_url.'?page='.($page-1));

      // We are not on the last page, so output a link to go "down" a page of entries.
      if (($page+1)*MAX_PER_PAGE < $num)
        $menu->add_down( $this_url.'?page='.($page+1));
      
      // Render the menu
      $menu->display();
    }
  }
  
  function display_message($id)
  {
    page_header( "Messages" );

    global $message_status_string;
    $menu = new menu();
    $data = array_pop(db_toarray("select * from messages where message_id=".$id));
    
    // Render the message
    echo '<table align="center" width="80%">';
    echo '<tr><td><font color="'.$_SESSION["opts"]["style"]["TITLE_COLOUR"].'">'
          .'Date:'.$data["ADDED"].'</td>'
          .'<td align="right"><font color="'.$_SESSION["opts"]["style"]["TITLE_COLOUR"].'">'
          .'Status:'.$message_status_string[$data["STATUS"]].'</td></tr>';
    echo '<tr><td colspan="2">&nbsp;</td></tr>';
    echo '<tr><td colspan="2" align="center"><font color="'.$_SESSION["opts"]["style"]["TITLE_COLOUR"].'">'
          .$data["TITLE"].'</font></td></tr>';
    echo '<tr><td colspan="2"><font color="'.$_SESSION["opts"]["style"]["TEXT_COLOR"].'">'
          .$data["MESSAGE_TEXT"].'</font></td></tr>';
    echo '</table><p>';

    // Add the keep and delete links, if there was a keep or delete page to return
    // to then ensure that the link returns the user to the correct page
    if(!empty($_REQUEST["pagekeep"]))
      $menu->add_item('Keep this message','messages.php?page='.$_REQUEST["pagekeep"]);
    else
      $menu->add_item('Keep this message','messages.php');

    if(!empty($_REQUEST["pagedel"]))
      $menu->add_item('Delete this message','messages.php?delete='.$id.'&page='.$_REQUEST["pagedel"]);
    else
      $menu->add_item('Delete this message','messages.php?delete='.$id);
      
    $menu->display();
    
    db_sqlcommand("UPDATE messages SET status=1 WHERE message_id=".$id);
  }

//*************************************************************************************************
// Main Code
//*************************************************************************************************

  if(!empty($_REQUEST["delete"]))
  {
    // Delete the message and display the list
    delete_message($_REQUEST["delete"]);
    list_messages();
  }
  else if(!empty($_REQUEST["id"]))
  {
    // There is a message selected, display it
    display_message($_REQUEST["id"]);
  }
  else
  {   
    // Just list the messages
    list_messages();
  }
   
  page_footer( 'config.php' );

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
