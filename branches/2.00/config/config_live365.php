<?php
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor
 *************************************************************************************************/

  require_once( realpath(dirname(__FILE__).'/../resources/radio/live365.php'));

  /**
   * Displays the Live365 configuration options to the user
   *
   * @param string $live365_msg - A success/fail message when updating Live365 settings
   * @param integer $live365_edit_id - The Row ID for editing the Live365 user credentials
   */

  function live365_display($live365_msg = '', $live365_edit_id = 0 )
  {
    $data = db_toarray("select u.user_id, u.name, un.value username, pw.value password
                          from users u left outer join user_prefs un on (un.user_id = u.user_id and un.name = 'LIVE365_USERNAME')
                                       left outer join user_prefs pw on (pw.user_id = u.user_id and pw.name = 'LIVE365_PASSWORD')
                      order by u.name");

    echo '<table width="100%"><tr>
          <td width=100% align="center"><a href="http://www.live365.com/" target="_blank"><img src="/images/logos/youandlive365.gif" height="60" width="468" border="1" alt="Join the Radio Revolution. Click Here for Live365"></a></td>
        </tr></table>';
    message($live365_msg);

    echo '<p>'.str('IRADIO_LIVE365_DESC','<a href="http://www.live365.com/" target="_blank">www.live365.com</a>');

    form_start("index.php", 150, "live365_auth");
    form_hidden('section','LIVE365');
    form_hidden('action','UPDATE');
    form_select_table( "user_id"
                     , $data
                     , str('LIVE365_LOGIN_HEADINGS')
                     , array("class"=>"form_select_tab","width"=>"100%")
                     , "user_id"
                     , array( "NAME"=>"!"
                            , "USERNAME"=>""
                            , "PASSWORD"=>"*"
                            )
                     , $live365_edit_id
                     , "live365_auth");
    if (!$live365_edit_id)
      form_submit(str('CONFIG_LASTFM_CLEAR'), 1 ,"center");
    form_end();
  }

  /**
   * Saves the Live365 login details
   *
   */

  function live365_update()
  {
    $selected    = form_select_table_vals("user_id");
    $edit_id     = form_select_table_edit("user_id", "live365_auth");
    $update_data = form_select_table_update("user_id", "live365_auth");

    if(!empty($edit_id))
    {
      live365_display("", $edit_id);
    }
    elseif(!empty($update_data))
    {
      $user_id  = $update_data["USER_ID"];
      $username = $update_data["USERNAME"];
      $password = $update_data["PASSWORD"];

      if (empty($username) || empty($password))
        live365_display("!".str('LASTFM_MISSING_LOGIN'));
      else
      {
        set_user_pref('LIVE365_USERNAME',$username, $user_id);
        set_user_pref('LIVE365_PASSWORD',$password, $user_id);
        $iradio = new live365;
        if ($iradio->signedin)
          live365_display(str('SAVE_SETTINGS_OK'));
        else
          live365_display("!".str('LOGIN_INVALID'));
      }
    }
    elseif(!empty($selected))
    {
      foreach($selected as $user_id)
        db_sqlcommand("delete from user_prefs where user_id=$user_id and name in ('LIVE365_USERNAME','LIVE365_PASSWORD')");

      live365_display(str('CONFIG_LASTFM_CLEAR_OK'));
    }
    else
      live365_display();
  }

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
