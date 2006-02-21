<?
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor
 *************************************************************************************************/
  
  // ----------------------------------------------------------------------------------
  // Display current config
  // ----------------------------------------------------------------------------------
  
  function users_display($modify_msg = '', $add_msg = '', $edit_id = 0)
  {
    $data = db_toarray("select user_id, u.Name 'Name', u.Pin, c.name 'Max Certificate Viewable' 
                          from users u, certificates c 
                         where u.maxcert=c.cert_id order by u.name asc");
    
    
    echo "<h1>".str('USERS_ADD_TITLE')."</h1>";
    message($modify_msg);
    form_start("index.php", 150, "users");
    form_hidden("section", "USERS");
    form_hidden("action", "MODIFY");
    form_select_table("user_id", $data, str('USERS_TABLE_HEADINGS')
                     ,array("class"=>"form_select_tab","width"=>"100%"), "user_id",
                      array("NAME"=>"",
                            "MAX CERTIFICATE VIEWABLE"=>"select cert_id,name from certificates order by rank asc",
                            "PIN"=>"*")
                      , $edit_id, "users");
    form_submit(str('USERS_DEL_BUTTON'), 1 ,"center");
    form_end();
    
    echo "<p><h1>".str('USERS_ADD_TITLE')."</h1>";
    message($add_msg);
    form_start("index.php", 150);
    form_hidden("section", "USERS");
    form_hidden("action", "NEW");
    form_input("name", str("NAME"), 70, '', $_REQUEST["name"]);
    form_label(str('USERS_NAME_PROMPT'));
    form_list_dynamic("cert", str('USERS_MAX_CERT'), "select cert_id,name from certificates order by rank asc", $_REQUEST["cert"]);
    form_label(str('USERS_MAX_CERT_PROMPT'));
    form_input('pin',str('USERS_PIN'),5,10);
    form_label(str('USERS_PIN_PROMPT'));
    form_submit(str('USERS_ADD_BUTTON'), 2);
    form_end();
  }
  
  function users_new()
  {
    $name = $_REQUEST["name"];
    $cert = $_REQUEST["cert"];
    $pin  = $_REQUEST["pin"];
    
    if(empty($name))
    {
      users_display("", "!".str('USERS_ERROR_NAME'));
    }
    elseif (empty($cert))
    {
      users_display("","!".str('USERS_ERROR_CERT'));
    }
    else
    {
      $user_count = db_value("select count(*) from users where name='".db_escape_str($name)."'");
      
      if($user_count > 0)
      {
        users_display("", "!".str('USERS_ERROR_EXISTS'));
      }
      else
      {
        $data = array("name"=>$name, "maxcert"=>$cert,'pin'=>$pin);

        if(db_insert_row("users", $data) === false)
          users_display(db_error());
        else
          users_display("", str('USERS_ADDED_OK'));
      }
    }
  }
  
  function users_modify()
  {
    $selected = form_select_table_vals("user_id");
    $edit_id = form_select_table_edit("user_id", "users");
    $update_data = form_select_table_update("user_id", "users");
    
    if(!empty($edit_id))
    {
      users_display("", "", $edit_id);
    }
    elseif(!empty($update_data))
    {
      $user_id = $update_data["USER_ID"];
      $name = $update_data["NAME"];
      $max_cert = $update_data["MAX_CERTIFICATE_VIEWABLE"];
      $pin = $update_data["PIN"];
      
      if(empty($name))
      {
        user_display("!".str('USERS_ERROR_NAME'));
      }
      else
      {
        $sql = "update users set name='".db_escape_str($name)."'";
        if(empty($pin))
          $sql = $sql.",pin=NULL";
        else
          $sql = $sql.",pin='".db_escape_str($pin)."'";
        
        $sql = $sql.",maxcert=$max_cert where user_id=$user_id";
        
        db_sqlcommand($sql);
        users_display(str('USERS_EDIT_OK'));
      }
    }
    elseif(!empty($selected))
    {
      $message = str('USERS_DEL_OK');
      
      foreach($selected as $selected_item)
      {
        if($selected_item != 1)
        {
          db_sqlcommand("delete from users where user_id=$selected_item");
        }
        else
          $message = "!".str('USERS_ERROR_DEFAULT');
      }
      
      users_display($message);
    }
    else
      users_display();
  }
  
/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>