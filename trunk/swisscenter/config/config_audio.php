<?
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor
 *************************************************************************************************/

require_once( realpath(dirname(__FILE__).'/../base/internet.php'));

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
    form_submit(str('SAVE_SETTINGS'));  
    form_end();
    
    /* 04.May.07 - MusicIP functionality not yet ready for deployment. This is a mock up of the "Slider" control.
      
    echo "<h1>".str('CONFIG_MUSICIP_OPTIONS')."</h1>";
    $opts_type = array( str('TRACKS')=>'tracks',str('MINUTES')=>'min');

    if ( !musicip_check(10002,1) )
      message('!MusicIP server not found');

    form_start('index.php');
    form_hidden('section','AUDIO');
    form_hidden('action','UPDATE_MUSICIP');
    form_input('test','Port',3,'', 10002);
    form_label(str('TTF_FONT_PROMPT'));    
      
    form_slider('test1a','Repeating Artists',1,100,3, 10);  
    form_radio_static('test1b','',$opts_type, 'tracks' ,false,true);
    form_label(str('TTF_FONT_PROMPT'));    
    form_slider('test2a','Playlist size',5,1000,3, 50);  
    form_radio_static('test2b','',$opts_type, 'tracks' ,false,true);
    form_label(str('TTF_FONT_PROMPT'));    
    form_slider('test3a','Style',0,200,3, 20);  
    form_label(str('TTF_FONT_PROMPT'));    
    form_slider('test4a','Variety',0,9,3, 0);  
    form_label(str('TTF_FONT_PROMPT'));    
    form_submit(str('SAVE_SETTINGS'));  
    form_end();
    
    */
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
