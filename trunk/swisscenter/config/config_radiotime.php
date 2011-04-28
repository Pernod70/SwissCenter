<?php
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor
 *************************************************************************************************/

  /**
   * Displays the Radiotime configuration options to the user
   *
   * @param string $radiotime_msg - A success/fail message when updating Radiotime settings
   * @param integer $radiotime_edit_id - The Row ID for editing the Radiotime user credentials
   */

  function radiotime_display($radiotime_msg = '', $radiotime_edit_id = 0 )
  {
    $data = db_toarray("select u.user_id, u.name, un.value username
                          from users u left outer join user_prefs un on (un.user_id = u.user_id and un.name = 'RADIOTIME_USERNAME')
                      order by u.name");

    echo '<p><h1>'.str('IRADIO_RADIOTIME').'</h1><p>';
    echo '<p>'.str('IRADIO_RADIOTIME_DESC','<a href="http://radiotime.com/">radiotime.com</a>');

    message($radiotime_msg);

    form_start("index.php", 150, "radiotime_auth");
    form_hidden('section','RADIOTIME');
    form_hidden('action','UPDATE');
    form_select_table( "user_id"
                     , $data
                     , str('RADIOTIME_LOGIN_HEADINGS')
                     , array("class"=>"form_select_tab","width"=>"100%")
                     , "user_id"
                     , array( "NAME"=>"!"
                            , "USERNAME"=>""
                            )
                     , $radiotime_edit_id
                     , "radiotime_auth");
    if (!$radiotime_edit_id)
      form_submit(str('CONFIG_LASTFM_CLEAR'), 1 ,"center");
    form_end();
  }

  /**
   * Saves the Radiotime login details
   *
   */

  function radiotime_update()
  {
    $selected    = form_select_table_vals("user_id");
    $edit_id     = form_select_table_edit("user_id", "radiotime_auth");
    $update_data = form_select_table_update("user_id", "radiotime_auth");

    if(!empty($edit_id))
    {
      radiotime_display("", $edit_id);
    }
    elseif(!empty($update_data))
    {
      $user_id  = $update_data["USER_ID"];
      $username = strtolower($update_data["USERNAME"]);

      if (empty($username))
        radiotime_display("!".str('RADIOTIME_MISSING_LOGIN'));
      else
      {
        set_user_pref('RADIOTIME_USERNAME',$username, $user_id);
        radiotime_display(str('SAVE_SETTINGS_OK'));
      }
    }
    elseif(!empty($selected))
    {
      foreach($selected as $user_id)
        db_sqlcommand("delete from user_prefs where user_id=$user_id and name='RADIOTIME_USERNAME'");

      radiotime_display(str('CONFIG_LASTFM_CLEAR_OK'));
    }
    else
      radiotime_display();
  }

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
