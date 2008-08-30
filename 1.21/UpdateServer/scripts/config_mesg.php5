<?php

/**************************************************************************************************
                                              Start of file
 ***************************************************************************************************/
  
  function mesg_display($delete = '', $new = '', $edit_id = '')
  {
    $data = db_toarray("select message_id, title, message_text from swiss_messages order by added desc");
    
    echo "<h1>Messages</h1>";
    message($delete);
    form_start('index.php5', 150, 'mesg');
    form_hidden('section','MESG');
    form_hidden('action','MODIFY');
    form_select_table('message_id',$data,array('class'=>'form_select_tab','width'=>'100%'),'message_id',
                      array('TITLE'=>'','MESSAGE_TEXT'=>''), $edit_id, 'mesg');
    form_submit('Remove Selected Messages',1,'center');
    form_end();
  
    echo '<p><h1>Add a filename<p>';
    message($new);
    form_start('index.php5');
    form_hidden('section','MESG');
    form_hidden('action','NEW');
    form_input('title','Title',30,'',un_magic_quote($_REQUEST['title']));
    form_text('text','Message Text',70,5,un_magic_quote($_REQUEST['text']));
    form_submit('Add Message',2);
    form_end();
  }
   
  function mesg_modify()
  {
    $selected = form_select_table_vals('message_id');
    $edit_id = form_select_table_edit('message_id', 'mesg');
    $update_data = form_select_table_update('message_id', 'mesg');
    
    if(!empty($edit_id))
    {
      mesg_display('', '', $edit_id);
    }
    else if(!empty($update_data))
    {
      
      $title = $update_data["TITLE"];
      $text  = $update_data["MESSAGE_TEXT"];
      
      if (empty($title))
        mesg_display('',"!Please enter a title");
      elseif (empty($text))
        mesg_display('',"!Please enter a message");
      else
      {
        db_sqlcommand("update swiss_messages set title='".db_escape_str($title)."' ,message_text='".db_escape_str($text)."' where message_id=".db_escape_str($update_data["MESSAGE_ID"]));
        mesg_display('Message updated');
      }
    }
    else if(!empty($selected))
    {
      foreach ($selected as $id)
        db_sqlcommand("delete from swiss_messages where message_id='".$id."'");

      mesg_display('The selected messages been removed.');
    }
    else
      mesg_display();
  }
  
  function mesg_new()
  {
    $title = un_magic_quote($_REQUEST["title"]);
    $text  = un_magic_quote($_REQUEST["text"]);
    
    if (empty($title))
      mesg_display('',"!Please enter a title");
    elseif (empty($text))
      mesg_display('',"!Please enter a message");
    else 
    {
      if ( db_insert_row('swiss_messages',array('ADDED'=>db_datestr(),'TITLE'=>$title,'MESSAGE_TEXT'=>$text)) === false)
        mesg_display(db_error());
      else
        mesg_display('Message Added');
    }
  }

/**************************************************************************************************
                                               End of file
 ***************************************************************************************************/
