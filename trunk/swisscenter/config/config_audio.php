<?php
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor
 *************************************************************************************************/

  // ----------------------------------------------------------------------------------
  // Display current config
  // ----------------------------------------------------------------------------------

  function audio_display( $message = '' )
  {
    $opts_vals = array( str('ENABLED')=>'YES',str('DISABLED')=>'NO',str('AUTOMATIC')=>'AUTO');

    echo "<h1>".str('CONFIG_AUDIO_OPTIONS')."</h1>";
    message($message);
    form_start('index.php');
    form_hidden('section','AUDIO');
    form_hidden('action','UPDATE');
    form_radio_static('style',str('NOW_PLAYING_SUPPORT'),$opts_vals, get_sys_pref('SUPPORT_NOW_PLAYING','AUTO') ,false,true);
    form_label(str('NOW_PLAYING_PROMPT'));    
    form_submit(str('SAVE_SETTINGS'));  
    form_end();
  }
  
  // ----------------------------------------------------------------------------------
  // Saves the new parameters
  // ----------------------------------------------------------------------------------

  function audio_update()
  {
    set_sys_pref('SUPPORT_NOW_PLAYING',$_REQUEST["style"]);
    audio_display(str('SAVE_SETTINGS_OK'));
  }

  /**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
