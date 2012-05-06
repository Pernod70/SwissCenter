<?php
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor
 *************************************************************************************************/

  require_once( realpath(dirname(__FILE__).'/../base/musicip.php'));

  // ----------------------------------------------------------------------------------
  // Display current config
  // ----------------------------------------------------------------------------------

  function musicip_display( $message = '')
  {
    echo "<h1>".str('CONFIG_MIP_OPTIONS')."</h1>";
    $opts_type = array( str('TRACKS')=>'tracks',str('MINUTES')=>'min');

    // Display any messages to the user regarding the state of the MusicIP service
    if ( ! empty($message) )
      message($message);
    elseif ( ! musicip_available() )
      message('!'.str('MIP_NOT_FOUND'));

    echo '<b>'.str('MIP_WHATIS').'</b>';
    echo '<p>'.str('MIP_DESC','<a href="http://www.musicip.com">www.musicip.com</a>');

    form_start('index.php');
    form_hidden('section','MUSICIP');
    form_hidden('action','UPDATE');

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

  function musicip_update()
  {
    $port = $_REQUEST["port"];

    if (empty($port) || (int)$port < 1 || (int)$port > 65535)
      musicip_display("!".str('MIP_INVALID_PORT'));
    else
    {
      if ( musicip_check($port) )
      {
        $style     = $_REQUEST["style"];
        $variety   = $_REQUEST["variety"];
        $size      = $_REQUEST["size"];
        $size_type = $_REQUEST["size_type"];
        $dupsize   = $_REQUEST["reject"];
        $duptype   = $_REQUEST["reject_type"];

        set_sys_pref('MUSICIP_PORT',$port);
        set_sys_pref('MUSICIP_STYLE',$style);
        set_sys_pref('MUSICIP_VARIETY',$variety);
        set_sys_pref('MUSICIP_REJECT',$dupsize);
        set_sys_pref('MUSICIP_REJECT_TYPE',$duptype);
        set_sys_pref('MUSICIP_SIZE',$size);
        set_sys_pref('MUSICIP_SIZE_TYPE',$size_type);

        $size_type = ($size_type == 'tracks') ? 0 : 1;
        $duptype   = ($duptype == 'tracks') ? 0 : 1;
        musicip_server_update($style, $variety, $size, $size_type, $dupsize, $duptype);
        musicip_display(str('SAVE_SETTINGS_OK'));
      }
      else
      {
        musicip_display("!".str('MIP_INVALID_PORT',$port));
      }
    }
  }

  /**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
