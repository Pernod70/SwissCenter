<?
//*************************************************************************************************
//  SWISScenter Source                                                              Robert Taylor
//*************************************************************************************************

  require_once("base/mysql.php");

  define('MESSAGE_STATUS_NEW', 0);
  define('MESSAGE_STATUS_READ', 1);
  define('MESSAGE_STATUS_DELETED', 2);
  
  $message_status_string = array(
      MESSAGE_STATUS_NEW => "New",
      MESSAGE_STATUS_READ => "Read",
      MESSAGE_STATUS_DELETED => "Deleted",
  );
  
  function count_messages_with_status($status)
  {
    return db_value("select count(*) from messages where status=".$status);
  }

  function count_new_and_unread_messages()
  {
    return db_value("select count(*) from messages where status<=".MESSAGE_STATUS_READ);
  }
  
  function get_new_and_unread_messages()
  {
    return db_toarray("select * from messages where status <= ".MESSAGE_STATUS_READ." order by added desc limit "
              .(($page*MAX_PER_PAGE)).",".MAX_PER_PAGE);
  }
?>
