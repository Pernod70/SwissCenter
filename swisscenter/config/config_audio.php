<?php
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor
 *************************************************************************************************/

  // ----------------------------------------------------------------------------------
  // Display current config
  // ----------------------------------------------------------------------------------

  function audio_display( $message = '' )
  {
    $opts_vals   = array( str('ENABLED')   => 'YES',
                          str('DISABLED')  => 'NO',
                          str('AUTOMATIC') => 'AUTO');
    $style_opts  = array( str('ORIGINAL')  => 'ORIGINAL',
                          str('ENHANCED')  => 'ENHANCED' );
    $fanart_vals = array( str('NOW_PLAYING_FANART_NONE')    => 'NONE',
                          str('NOW_PLAYING_FANART_GOOGLE')  => 'GOOGLE',
                          str('NOW_PLAYING_FANART_DISCOGS') => 'DISCOGS' );

    echo "<h1>".str('CONFIG_AUDIO_OPTIONS')."</h1>";
    message($message);

    form_start('index.php');
    form_hidden('section','AUDIO');
    form_hidden('action','UPDATE');
    form_radio_static('support',str('NOW_PLAYING_SUPPORT'),$opts_vals, get_sys_pref('SUPPORT_NOW_PLAYING','AUTO'), false,true);
    form_label(str('NOW_PLAYING_PROMPT'));

    form_radio_static('style',str('NOW_PLAYING_STYLE'),$style_opts, get_sys_pref('NOW_PLAYING_STYLE','ORIGINAL'), false,true);
    form_label(str('NOW_PLAYING_STYLE_PROMPT'));

    form_list_static('fanart',str('NOW_PLAYING_FANART'),$fanart_vals,get_sys_pref('NOW_PLAYING_FANART','DISCOGS'), false,false,false);
    form_label(str('NOW_PLAYING_FANART_PROMPT'));
    form_slider('quality',str('NOW_PLAYING_FANART_QUALITY'),0,500,3, get_sys_pref('NOW_PLAYING_FANART_QUALITY',0));
    form_label(str('NOW_PLAYING_FANART_QUALITY_PROMPT'));
    form_submit(str('SAVE_SETTINGS'));
    form_end();
  }

  // ----------------------------------------------------------------------------------
  // Saves the new parameters
  // ----------------------------------------------------------------------------------

  function audio_update()
  {
    set_sys_pref('SUPPORT_NOW_PLAYING',$_REQUEST["support"]);
    set_sys_pref('NOW_PLAYING_STYLE',$_REQUEST["style"]);
    set_sys_pref('NOW_PLAYING_FANART',$_REQUEST["fanart"]);
    set_sys_pref('NOW_PLAYING_FANART_QUALITY',$_REQUEST["quality"]);
    audio_display(str('SAVE_SETTINGS_OK'));
  }

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
