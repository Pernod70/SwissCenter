<?php
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor
 *************************************************************************************************/

  /**
   * Displays the TuneIn Radio configuration options to the user
   *
   * @param string $tunein_msg - A success/fail message when updating TuneIn Radio settings
   * @param integer $tunein_edit_id - The Row ID for editing the TuneIn Radio user credentials
   */

  function tunein_display($tunein_msg = '', $tunein_edit_id = 0 )
  {
    $data = db_toarray("select u.user_id, u.name, un.value username
                          from users u left outer join user_prefs un on (un.user_id = u.user_id and un.name = 'RADIOTIME_USERNAME')
                      order by u.name");

    echo '<p><h1>'.str('IRADIO_TUNEIN').'</h1><p>';
    echo '<p>'.str('IRADIO_TUNEIN_DESC','<a href="http://tunein.com/">tunein.com</a>');

    message($tunein_msg);

    form_start("index.php", 150, "tunein_auth");
    form_hidden('section','TUNEIN');
    form_hidden('action','UPDATE');
    form_select_table( "user_id"
                     , $data
                     , str('TUNEIN_LOGIN_HEADINGS')
                     , array("class"=>"form_select_tab","width"=>"100%")
                     , "user_id"
                     , array( "NAME"=>"!"
                            , "USERNAME"=>""
                            )
                     , $tunein_edit_id
                     , "tunein_auth");
    if (!$tunein_edit_id)
      form_submit(str('CONFIG_LASTFM_CLEAR'), 1 ,"center");
    form_end();
  }

  /**
   * Saves the TuneIn Radio login details
   *
   */

  function tunein_update()
  {
    $selected    = form_select_table_vals("user_id");
    $edit_id     = form_select_table_edit("user_id", "tunein_auth");
    $update_data = form_select_table_update("user_id", "tunein_auth");

    if(!empty($edit_id))
    {
      tunein_display("", $edit_id);
    }
    elseif(!empty($update_data))
    {
      $user_id  = $update_data["USER_ID"];
      $username = strtolower($update_data["USERNAME"]);

      if (empty($username))
        tunein_display("!".str('TUNEIN_MISSING_LOGIN'));
      else
      {
        set_user_pref('RADIOTIME_USERNAME',$username, $user_id);
        tunein_display(str('SAVE_SETTINGS_OK'));
      }
    }
    elseif(!empty($selected))
    {
      foreach($selected as $user_id)
        db_sqlcommand("delete from user_prefs where user_id=$user_id and name='TUNEIN_USERNAME'");

      tunein_display(str('CONFIG_LASTFM_CLEAR_OK'));
    }
    else
      tunein_display();
  }

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
