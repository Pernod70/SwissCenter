<?
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor
 *************************************************************************************************/

  // ----------------------------------------------------------------------------------
  // Display current config
  // ----------------------------------------------------------------------------------

  function audio_display( $message = '')
  {
    $opts2_vals  = array( str('ENABLED')=>'YES',str('DISABLED')=>'NO');
    $opts3_vals = array( str('ENABLED')=>'YES',str('DISABLED')=>'NO',str('AUTOMATIC')=>'AUTO');

    echo "<h1>".str('CONFIG_AUDIO_OPTIONS')."</h1>";
    message($message);
    form_start('index.php');
    form_hidden('section','AUDIO');
    form_hidden('action','UPDATE');

    form_radio_static('style',str('NOW_PLAYING_SUPPORT'),$opts3_vals, get_sys_pref('SUPPORT_NOW_PLAYING','AUTO') ,false,true);
    form_label(str('NOW_PLAYING_PROMPT'));    
    
    form_end();
  }

  // ----------------------------------------------------------------------------------
  // Saves the new parameters
  // ----------------------------------------------------------------------------------

  function audio_update()
  {
    $dir = rtrim(str_replace('\\','/',stripslashes($_REQUEST["dir"])),'/');
    $size = $_REQUEST["size"];

    if (empty($dir))
      cache_display("!".str('CACHE_ERROR_MISSING'));
    elseif ($size == '')
      cache_display("!".str('CACHE_ERROR_SIZE'));
    elseif (! form_mask($size,'[0-9]'))
      cache_display("!".str('CACHE_ERROR_NOT_NUMBER'));
    elseif ( $size <0 )
      cache_display("!".str('CACHE_ERROR_SMALL'));
    elseif (!file_exists($dir))
      cache_display("!".str('CACHE_ERROR_NOT_EXIST'));
    elseif ( ($dir[0] != '/' && $dir[1] != ':') || $dir=='..' || $dir=='.')
      cache_display("!".str('CACHE_ERROR_FULL_DIR'));
    else
    {
      set_sys_pref('CACHE_STYLE_DETAILS',$_REQUEST["style"]);
      set_sys_pref('CACHE_LANGUAGE_STRINGS',$_REQUEST["lang"]);
      set_sys_pref('CACHE_DIR',$dir);
      set_sys_pref('CACHE_MAXSIZE_MB',$size);
      cache_display(str('SAVE_SETTINGS_OK'));
    }
  }

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
