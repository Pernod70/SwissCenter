<?php
/**************************************************************************************************
   SWISScenter Source                                                              Nigel Barnes
 *************************************************************************************************/

  require_once( realpath(dirname(__FILE__).'/../resources/video/youtube_api.php'));

  // ----------------------------------------------------------------------------------
  // Display current config
  // ----------------------------------------------------------------------------------

  function youtube_display( $message = '')
  {
    $option_vals  = array( str('YES')=>'YES',str('NO')=>'NO');

    echo "<h1>".str('YOUTUBE')."</h1>";
    message($message);

    echo '<p>'.str('YOUTUBE_DESC','<a href="http://www.youtube.com" target="_blank">www.youtube.com</a>');

    form_start('index.php');
    form_hidden('section','YOUTUBE');
    form_hidden('action','UPDATE');
    echo '<p><b>'.str('YOUTUBE_USERNAMES').'</b>';

    foreach ( db_toarray("select * from users order by name") as $row)
      form_input($row["USER_ID"], $row["NAME"], 30, '', get_user_pref('YOUTUBE_USERNAME', '', $row["USER_ID"]), true);

    form_label(str('YOUTUBE_USERID_PROMPT'));

    form_radio_static('hd', str('YOUTUBE_HD'), $option_vals, get_sys_pref('YOUTUBE_HD','YES'), false, true);
    form_label(str('YOUTUBE_HD_PROMPT'));

    form_submit(str('SAVE_SETTINGS'));
    form_end();
  }

  // ----------------------------------------------------------------------------------
  // Saves the YouTube settings
  // ----------------------------------------------------------------------------------

  function youtube_update()
  {
    $youtube = new phpYouTube();

    foreach ( db_toarray("select * from users order by name") as $row)
    {
      $username = $_REQUEST[$row["USER_ID"]];

      // Check that username is valid
      if (!empty($username) && !$youtube->entryUserProfile($username))
        $message = "!".str('YOUTUBE_UNKNOWN_USER',$username);
      else
        set_user_pref('YOUTUBE_USERNAME', $username, $row["USER_ID"]);
    }
    set_sys_pref('YOUTUBE_HD', $_REQUEST['hd']);

    if ( !empty($message) )
      youtube_display($message);
    else
      youtube_display(str('SAVE_SETTINGS_OK'));
  }

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
