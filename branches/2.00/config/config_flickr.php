<?php
/**************************************************************************************************
   SWISScenter Source                                                              Nigel Barnes
 *************************************************************************************************/

  require_once( realpath(dirname(__FILE__).'/../base/flickr.php'));

  // ----------------------------------------------------------------------------------
  // Display current config
  // ----------------------------------------------------------------------------------

  function flickr_display( $message = '')
  {
    echo "<h1>".str('CONFIG_FLICKR')."</h1>";

    // Display any messages to the user regarding the state of the MusicIP service
    if ( !empty($message) )
      message($message);

    echo '<p>'.str('FLICKR_DESC','<a href="http://www.flickr.com">www.flickr.com</a>');

    form_start('index.php');
    form_hidden('section','FLICKR');
    form_hidden('action','UPDATE');
    echo '<p><b>'.str('FLICKR_USERNAMES').'</b>';

    foreach ( db_toarray("select * from users order by name") as $row)
      form_input($row["USER_ID"],$row["NAME"],30,'', get_user_pref('FLICKR_USERNAME','',$row["USER_ID"]), true);

    form_label(str('FLICKR_USERID_PROMPT'));

    form_submit(str('SAVE_SETTINGS'));
    form_end();
  }

  // ----------------------------------------------------------------------------------
  // Saves the Flickr settings
  // ----------------------------------------------------------------------------------

  function flickr_update()
  {
    $flickr = new phpFlickr(FLICKR_API_KEY, FLICKR_API_SECRET);
    $flickr->enableCache("db");

    foreach ( db_toarray("select * from users order by name") as $row)
    {
      $userid = $_REQUEST[$row["USER_ID"]];
      $user   = $flickr->people_findByUsername($userid);

      if ( empty($userid) || !isset($user["nsid"]) )
        $message = "!".str('FLICKR_UNKNOWN_USER',$userid);
      else
      {
        set_user_pref('FLICKR_USERNAME', $user["username"], $row["USER_ID"]);
        set_user_pref('FLICKR_USERID', $user["nsid"], $row["USER_ID"]);
      }
    }
    if ( !empty($message) )
      flickr_display($message);
    else
      flickr_display(str('SAVE_SETTINGS_OK'));
  }

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
