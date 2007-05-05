<?
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor
 *************************************************************************************************/

require_once( realpath(dirname(__FILE__).'/../base/musicip.php'));

  // ----------------------------------------------------------------------------------
  // Display current config
  // ----------------------------------------------------------------------------------

  function audio_display( $message_audio = '', $message_musicip = '')
  {
    $opts2_vals  = array( str('ENABLED')=>'YES',str('DISABLED')=>'NO');
    $opts3_vals = array( str('ENABLED')=>'YES',str('DISABLED')=>'NO',str('AUTOMATIC')=>'AUTO');

    echo "<h1>".str('CONFIG_AUDIO_OPTIONS')."</h1>";
    message($message_audio);
    form_start('index.php');
    form_hidden('section','AUDIO');
    form_hidden('action','UPDATE');
    form_radio_static('style',str('NOW_PLAYING_SUPPORT'),$opts3_vals, get_sys_pref('SUPPORT_NOW_PLAYING','AUTO') ,false,true);
    form_label(str('NOW_PLAYING_PROMPT'));    
    form_submit(str('SAVE_SETTINGS'));  
    form_end();
    
    echo "<h1>".str('CONFIG_MIP_OPTIONS')."</h1>";
    $opts_type = array( str('TRACKS')=>'tracks',str('MINUTES')=>'min');

    message($message_musicip);      
    echo str('MIP_DESC','<a href="www.musicip.com">www.musicip.com</a>');

    form_start('index.php');
    form_hidden('section','AUDIO');
    form_hidden('action','UPDATE_MUSICIP');
    
    form_input('port',str('MIP_PORT'),3,'', get_sys_pref('MUSICIP_PORT'));
    form_label(str('MIP_PORT_PROMPT'));    
      
    form_slider('size',str('MIP_SIZE'),5,500,3, get_sys_pref('MUSICIP_SIZE',20));  
    form_radio_static('size_type','',$opts_type, get_sys_pref('MUSICIP_SIZE_TYPE','tracks') ,false,true);
    form_label(str('MIP_SIZE_PROMPT'));    
    
    form_slider('reject',str('MIP_REJECT'),1,100,3, get_sys_pref('MUSICIP_REJECT',5));  
    form_radio_static('reject_type','',$opts_type, get_sys_pref('MUSICIP_REJECT_TYPE','tracks') ,false,true);
    form_label(str('MIP_REJECT_PROMPT'));    
    
    form_slider('style',str('MIP_STYLE'),0,200,3, get_sys_pref('MUSICIP_STYLE',20));  
    form_label(str('MIP_STYLE_PROMPT'));    
    
    form_slider('variety',str('MIP_VARIETY'),0,9,3, get_sys_pref('MUSICIP_VARIETY',0));  
    form_label(str('MIP_VARIETY_PROMPT'));    
    
    form_submit(str('SAVE_SETTINGS'));  
    form_end();    
  }

  // ----------------------------------------------------------------------------------
  // Saves the MusicIP settings
  // ----------------------------------------------------------------------------------

  function audio_update_musicip()
  {
    $port = $_REQUEST["port"];

    if (empty($port) || (int)$port < 1 || (int)$port > 65535)
      audio_display('',"!".str('MIP_INVALID_PORT'));
    else
    {
      if ( musicip_check($port) )
      {
        set_sys_pref('MUSICIP_PORT',$port);
        set_sys_pref('MUSICIP_STYLE',$_REQUEST["style"]);
        set_sys_pref('MUSICIP_VARIETY',$_REQUEST["variety"]);
        set_sys_pref('MUSICIP_REJECT',$_REQUEST["reject"]);
        set_sys_pref('MUSICIP_REJECT_TYPE',$_REQUEST["reject_type"]);
        set_sys_pref('MUSICIP_SIZE',$_REQUEST["size"]);
        set_sys_pref('MUSICIP_SIZE_TYPE',$_REQUEST["size_type"]);
        audio_display('',str('SAVE_SETTINGS_OK'));
      }
      else 
      {
        audio_display('',"!".str('MIP_NOT_FOUND',$port));
      }
    }
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
