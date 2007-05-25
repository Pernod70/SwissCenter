<?
//*************************************************************************************************
//  SWISScenter Source                                                              Robert Taylor
//*************************************************************************************************

  require_once( realpath(dirname(__FILE__).'/base/mysql.php'));

  define('MESSAGE_STATUS_NEW'    , 0);
  define('MESSAGE_STATUS_READ'   , 1);
  define('MESSAGE_STATUS_DELETED', 2);
  
  $message_status_string = array(
      MESSAGE_STATUS_NEW     => str('MESSAGE_STATUS_NEW'),
      MESSAGE_STATUS_READ    => str('MESSAGE_STATUS_READ'),
      MESSAGE_STATUS_DELETED => str('MESSAGE_STATUS_DELETED'),
  );
  
  function delete_message($delete_id)
  {
    // Delete the message from the database
    return db_sqlcommand("UPDATE messages 
                                 SET status=".MESSAGE_STATUS_DELETED." 
                               WHERE message_id=".$delete_id);
  }
  
  function count_messages_with_status($status)
  {
    return db_value("SELECT count(*) 
                       FROM messages
                      WHERE status=".$status);
  }

  function count_new_and_unread_messages()
  {
    return db_value("SELECT count(*)
                       FROM messages 
                      WHERE status<=".MESSAGE_STATUS_READ);
  }
  
  function get_new_and_unread_messages( $page = 0 )
  {
    return db_toarray("SELECT * 
                         FROM messages
                        WHERE status IN (".MESSAGE_STATUS_NEW.",".MESSAGE_STATUS_READ.") 
                     ORDER BY added desc 
                        LIMIT ".($page*MAX_PER_PAGE).",".MAX_PER_PAGE);
  }
?>
