<?
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor
 *************************************************************************************************/

  require_once("base/page.php");

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

    $menu->add_item('Keep this messge','');
    $menu->add_item('Delete this message','');
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
   
  page_footer( 'config.php' );

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
