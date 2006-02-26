<?
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor
 *************************************************************************************************/
  
  // ----------------------------------------------------------------------------------
  // Display current config
  // ----------------------------------------------------------------------------------
  
  function playlists_display( $message = '' )
  {
    $dir  = (!empty($_REQUEST["location"]) ? $_REQUEST["location"] : db_value("select value from system_prefs where name='PLAYLISTS'"));
  
    echo '<p><h1>'.str('PLAYLISTS').'<p>';
    message($message);
    form_start('index.php');
    form_hidden('section','PLAYLISTS');
    form_hidden('action','UPDATE');
    form_input('location',str('LOCATION'),70,'',$dir);
    form_label(str('PLAYLISTS_CONFIG_PROMPT'));
    form_submit(str('SAVE_SETTINGS'),2);
    form_end();
  }
  
  // ----------------------------------------------------------------------------------
  // Saves the new parameter
  // ----------------------------------------------------------------------------------
  
  function playlists_update()
  {
    $dir = rtrim(str_replace('\\','/',un_magic_quote($_REQUEST["location"])),'/');
    
    if (empty($_REQUEST["location"]))
      playlists_display("!".str('PLAYLISTS_ERROR_DIR'));
    elseif (!file_exists($dir))
      playlists_display("!".str('PLAYLISTS_ERROR_INVALID'));
    elseif ( ($dir[0] != '/' && $dir[1] != ':') || $dir=='..' || $dir=='.')
      playlists_display("!".str('PLAYLISTS_ERROR_PATH'));
    else 
    {
      db_sqlcommand("delete from system_prefs where name='PLAYLISTS'");
      if ( db_insert_row('system_prefs',array("name"=>"PLAYLISTS","value"=>$dir)) === false)
      {
        playlists_display(db_error());
      }
      else
      {
        playlists_display(str('SAVE_SETTINGS_OK'));
      }
    }
  }
  
/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>