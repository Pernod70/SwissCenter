<?php
//*************************************************************************************************
//  SWISScenter Source                                                              Robert Taylor
//*************************************************************************************************

  require_once( realpath(dirname(__FILE__).'/base/page.php'));
  require_once( realpath(dirname(__FILE__).'/messages_db.php'));

  function list_messages()
  {
    // Display a message if there are no messages to display, otherwise list them
    // Get the number of new or read messages
    $num = count_new_and_unread_messages();

    if ($num == 0)
    {
      page_inform(2, page_hist_previous(), str('MESSAGES'), str('MESSAGES_NONE'));
      exit;
    }
    else
    {
      page_header( str('MESSAGES') );
      // Display a list of all the outstanding messages
      echo '<center>'.font_tags(FONTSIZE_BODY, style_value("PAGE_TEXT")).str('MESSAGES_SELECT').'</center></br>';

      // Find out what page we are on, default to page 0 (first page)
      $page = $_REQUEST["page"];
      if (empty($page))
        $page = 0;

      // Get the data from the database for the current page
      $data = get_new_and_unread_messages( $page );

      // List all of the messages in a menu
      $menu = new menu();
      foreach ($data as $row)
      {
        if($row["STATUS"] == MESSAGE_STATUS_NEW)
          $message_text = '('.str('MESSAGE_STATUS_NEW').') '.$row["TITLE"];
        else
          $message_text = $row["TITLE"];

        // Calculate the page that the user should be returned to if they choose to delete
        // this message. Should return to the current page unless this is the last item on
        // the page in which case they'll go back to the previous page unless this is page 0
        // in which case they'll always go back to page 0
        if ((count($data) > 1) || ($page == 0))
          $pagedel = $page;
        elseif ($page > 0)
          $pagedel = $page - 1;

        // Add a link to display the message, this includes the pages that should be displayed
        // if the keep or delete options are chosen
        $menu->add_item($message_text,'messages.php?id='.$row["MESSAGE_ID"].'&pagekeep='.$page
              .'&pagedel='.$pagedel);
      }

      // Add up/down buttons as needed for prev/next page
      $last_page = ceil($num/MAX_PER_PAGE)-1;
      if ($num > MAX_PER_PAGE)
      {
        $menu->add_up( '?page='.($page > 0 ? ($page-1) : $last_page));
        $menu->add_down( '?page='.($page < $last_page ? ($page+1) : 0));
      }

      // Render the menu
      $menu->display();
    }
  }

  function display_message($id)
  {
    page_header( str('MESSAGES') );

    global $message_status_string;
    $menu = new menu();
    $data = array_pop(db_toarray("select * from messages where message_id=".$id));

    // Render the message
    echo '<table align="center" width="80%">';
    echo '<tr><td>'.font_tags(FONTSIZE_BODY, style_value("PAGE_TITLE_COLOUR"))
          .str('DATE').':'.$data["ADDED"].'</td>'
          .'<td align="right">'.font_tags(FONTSIZE_BODY, style_value("PAGE_TITLE_COLOUR"))
          .str('STATUS').':'.$message_status_string[$data["STATUS"]].'</td></tr>';
    echo '<tr><td height="8" colspan="2"><img src="/images/dot.gif"></td></tr>';
    echo '<tr><td colspan="2" align="center">'.font_tags(FONTSIZE_BODY, style_value("PAGE_TITLE_COLOUR"))
          .$data["TITLE"].'</td></tr>';
    echo '<tr><td colspan="2">'.font_tags(FONTSIZE_BODY, style_value("PAGE_TEXT"))
          .$data["MESSAGE_TEXT"].'</td></tr>';
    echo '</table><p>';

    // Add the keep and delete links, if there was a keep or delete page to return
    // to then ensure that the link returns the user to the correct page
    if(!empty($_REQUEST["pagekeep"]))
      $menu->add_item(str('MESSAGES_KEEP'),'messages.php?page='.$_REQUEST["pagekeep"].'&hist='.PAGE_HISTORY_DELETE);
    else
      $menu->add_item(str('MESSAGES_KEEP'),'messages.php?&hist='.PAGE_HISTORY_DELETE);

    if(!empty($_REQUEST["pagedel"]))
      $menu->add_item(str('MESSAGES_DELETE'),'messages.php?delete='.$id.'&page='.$_REQUEST["pagedel"].'&hist='.PAGE_HISTORY_DELETE);
    else
      $menu->add_item(str('MESSAGES_DELETE'),'messages.php?delete='.$id.'&hist='.PAGE_HISTORY_DELETE);

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

  page_footer( page_hist_previous() );

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
