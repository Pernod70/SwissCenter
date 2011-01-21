<?php
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor
 *************************************************************************************************/

  require_once( realpath(dirname(__FILE__).'/../ext/lastfm/lastfm.php'));

  /**
   * Displays the Last.FM configuration options to the user
   *
   * @param string $lastfm_msg - A success/fail message when updating lastFM settings
   * @param integer $lastfm_edit_id - The Row ID for editing the lastFM user credentials
   */

  function lastfm_display($lastfm_msg = '', $lastfm_edit_id = 0 )
  {
    $option_vals  = array( str('ENABLED')=>'YES',str('DISABLED')=>'NO');
    $data = db_toarray("select u.user_id, u.name, un.value username, pw.value password
                             , ( CASE sc.value  WHEN 'NO' THEN '".str('NO')."' WHEN 'YES' THEN '".str('YES')."' ELSE '".str('NO')."' END) scrobble
                             , ( CASE im.value  WHEN 'NO' THEN '".str('NO')."' WHEN 'YES' THEN '".str('YES')."' ELSE '".str('NO')."' END) images
                          from users u left outer join user_prefs un on (un.user_id = u.user_id and un.name = 'LASTFM_USERNAME')
                                       left outer join user_prefs pw on (pw.user_id = u.user_id and pw.name = 'LASTFM_PASSWORD')
                                       left outer join user_prefs sc on (sc.user_id = u.user_id and sc.name = 'LASTFM_SCROBBLE')
                                       left outer join user_prefs im on (im.user_id = u.user_id and im.name = 'LASTFM_IMAGES')
                      order by u.name");

    echo '<p><h1>'.str('CONFIG_LASTFM_TITLE').'</h1><p>';
    echo '<b>'.str('LASTFM_WHATIS').'</b>';
    echo '<p><a href="http://www.last.fm/" target="_blank"><img src="/images/logos/lastfm.gif" align="right" hspace="20" vspace="16" border=0></a>'.str('LASTFM_ABOUT');

    message($lastfm_msg);

    form_start("index.php", 150, "lastfm_auth");
    form_hidden('section','LASTFM');
    form_hidden('action','UPDATE');
    form_select_table( "user_id"
                     , $data
                     , str('LASTFM_LOGIN_HEADINGS')
                     , array("class"=>"form_select_tab","width"=>"100%")
                     , "user_id"
                     , array( "NAME"=>"!"
                            , "USERNAME"=>""
                            , "PASSWORD"=>"*"
                            , "SCROBBLE"=>array( array("VAL"=>'NO',"NAME"=>str('NO')),array("VAL"=>'YES',"NAME"=>str('YES')))
                            , "IMAGES"=>array( array("VAL"=>'NO',"NAME"=>str('NO')),array("VAL"=>'YES',"NAME"=>str('YES')))
                            )
                     , $lastfm_edit_id
                     , "lastfm_auth");
    if (!$lastfm_edit_id)
      form_submit(str('CONFIG_LASTFM_CLEAR'), 1 ,"center");
    form_end();

    // Display the status of the Last.fm services
    $status = lastfm_status();
    if ( is_array($status) )
    {
      form_start("index.php", 150, "lastfm_status");
      echo '<b>'.str('LASTFM_STATUS').'</b>';
      for ($i=0; $i<=count($status[1])-1; $i++)
      {
        echo '<tr><td>'.$status[1][$i].'</td><td><img src="'.$status[2][$i].'">'.$status[3][$i].'</td></tr>';
      }
      form_end();
    }
  }

  /**
   * Saves the LastFM login details and options
   *
   */

  function lastfm_update()
  {
    $selected    = form_select_table_vals("user_id");
    $edit_id     = form_select_table_edit("user_id", "lastfm_auth");
    $update_data = form_select_table_update("user_id", "lastfm_auth");

    if(!empty($edit_id))
    {
      lastfm_display("", $edit_id);
    }
    elseif(!empty($update_data))
    {
      $user_id  = $update_data["USER_ID"];
      $username = $update_data["USERNAME"];
      $password = $update_data["PASSWORD"];
      $scrobble = $update_data["SCROBBLE"];
      $download = $update_data["IMAGES"];

      if (empty($username) || empty($password))
        lastfm_display("!".str('LASTFM_MISSING_LOGIN'));
      else
      {
        // Bit of a kludge, but if the password is characters then it's probably already a hash.
        if ( strlen($password) != 32 )
          $password = md5($password);

        set_user_pref('LASTFM_USERNAME',$username, $user_id);
        set_user_pref('LASTFM_PASSWORD',$password, $user_id);
        set_user_pref('LASTFM_SCROBBLE',$scrobble, $user_id);
        set_user_pref('LASTFM_IMAGES'  ,$download, $user_id);
        lastfm_display(str('SAVE_SETTINGS_OK'));
      }
    }
    elseif(!empty($selected))
    {
      foreach($selected as $user_id)
        db_sqlcommand("delete from user_prefs where user_id=$user_id and name in ('LASTFM_USERNAME','LASTFM_PASSWORD','LASTFM_SCROBBLE')");

      lastfm_display(str('CONFIG_LASTFM_CLEAR_OK'));
    }
    else
      lastfm_display();
  }

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
