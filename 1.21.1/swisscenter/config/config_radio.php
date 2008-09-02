<?php
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor
 *************************************************************************************************/
  
  /**
   * Displays the Internet Radio configuration options to the user
   *
   * @param string $lastfm_msg - A success/fail message when updating lastFM settings
   * @param string $shoutmsg - A success/fail message when updating Shoutcast settings
   * @param integer $lastfm_edit_id - The Row ID for editing the lastFM user credentials
   */
  
  function radio_display($lastfm_msg = '', $shoutmsg = '', $lastfm_edit_id = 0 )
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
    echo '<p><a href="http://www.last.fm/" target="_blank"><img src="/images/lastfm.gif" align="right" hspace="20" vspace="16" border=0></a>'.str('LASTFM_ABOUT');

    message($lastfm_msg);

    form_start("index.php", 150, "lastfm_auth");
    form_hidden('section','RADIO');
    form_hidden('action','UPDATE_LASTFM');
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

    echo '<p><h1>'.str('CONFIG_SHOUTCAST_TITLE').'</h1><p>';
    message($shoutmsg);
    form_start('index.php');
    form_hidden('section','RADIO');
    form_hidden('action','UPDATE_SHOUTCAST');
    form_input('maxnum',str('IRADIO_MAX_STATIONS'),20,'2',get_sys_pref('iradio_max_stations',24));
    form_label(str('IRADIO_MAX_STATIONS_PROMPT'));
    form_input('cache_expire',str('IRADIO_CACHE_EXPIRE'),20,'',get_sys_pref('iradio_cache_expire',3600));
    form_label(str('IRADIO_CACHE_EXPIRE_PROMPT'));
    form_submit(str('SAVE_SETTINGS'),2);
    form_end();
  }

  /**
   * Saves the LastFM login details and options
   *
   */
  
  function radio_update_lastfm()
  {
    $selected    = form_select_table_vals("user_id");
    $edit_id     = form_select_table_edit("user_id", "lastfm_auth");
    $update_data = form_select_table_update("user_id", "lastfm_auth");
    
    if(!empty($edit_id))
    {
        radio_display("", "", $edit_id);
    }
    elseif(!empty($update_data))
    {    	
      $user_id  = $update_data["USER_ID"];
      $username = $update_data["USERNAME"];
      $password = $update_data["PASSWORD"];
      $scrobble = $update_data["SCROBBLE"];
      $download = $update_data["IMAGES"];
      
  	  if (empty($username) || empty($password))
        radio_display("!".str('LASTFM_MISSING_LOGIN'));
      else 
      {
        // Bit of a kludge, but if the password is characters then it's probably already a hash.
        if ( strlen($password) != 32 )
          $password = md5($password);

        set_user_pref('LASTFM_USERNAME',$username, $user_id);
        set_user_pref('LASTFM_PASSWORD',$password, $user_id);
        set_user_pref('LASTFM_SCROBBLE',$scrobble, $user_id);
        set_user_pref('LASTFM_IMAGES'  ,$download, $user_id);
        radio_display(str('SAVE_SETTINGS_OK'));
      }      
    }
    elseif(!empty($selected))
    {
      foreach($selected as $user_id)
        db_sqlcommand("delete from user_prefs where user_id=$user_id and name in ('LASTFM_USERNAME','LASTFM_PASSWORD','LASTFM_SCROBBLE')");
      
      radio_display(str('CONFIG_LASTFM_CLEAR_OK'));
    }
    else
      radio_display();  	
  }
  
  /**
   * Saves the Shoutcast and LiveRadio options.
   *
   */
  
  function radio_update_shoutcast()
  {
    $maxnum = (int) $_REQUEST["maxnum"];
    $cache_expire = (int) $_REQUEST["cache_expire"];
    if (empty($cache_expire)) $cache_expire = 0;
    
    if (empty($_REQUEST["maxnum"]))
      radio_display('', "!".str('IRADIO_ERROR_MAXNUM'));
    elseif (empty($maxnum))
      radio_display('', "!".str('IRADIO_ERROR_MAXNUM_ZERO'));
    elseif (empty($_REQUEST["cache_expire"]))
      radio_display('', "!".str('IRADIO_ERROR_CACHE_EXPIRE'));
    else 
    {
      set_sys_pref('iradio_max_stations',$maxnum);
      set_sys_pref('iradio_cache_expire',$cache_expire);
      radio_display('', str('SAVE_SETTINGS_OK'));
    }
  }
  
/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
