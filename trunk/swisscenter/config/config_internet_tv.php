<?php
/**************************************************************************************************
   SWISScenter Source                                                              Nigel Barnes
 *************************************************************************************************/

require_once( realpath(dirname(__FILE__).'/../resources/video/toma_internet_tv.php'));

// ----------------------------------------------------------------------------------
// Displays the various internet TV options
// ----------------------------------------------------------------------------------

function internet_tv_display($message = '', $tomamsg = '')
{
  $media_names = array( str('TOMA_WM')=>'TOMA_SHOW_MEDIAPLAYER',
                        str('TOMA_RP')=>'TOMA_SHOW_REALPLAYER',
                        str('TOMA_WA')=>'TOMA_SHOW_WINAMP');

//  echo "<h1>".str('INTERNET_TV_OPTIONS')."</h1>";
//  message($message);

  // TOMA - Internet TV options
  echo "<p><h1>".str('TOMA_INTERNET_TV')."</h1>";
  message($tomamsg);
  echo '<p>'.str('TOMA_TV_TEXT','<a href="http://www.jlc-software.com/?page=internet_tv.html">TOMA - Internet TV</a>');

  form_start('index.php');
  form_hidden('section', 'INTERNET_TV');
  form_hidden('action', 'TOMA_UPDATE');
  form_checkbox_static('media',str('TOMA_MEDIA_TYPES'),$media_names,array('TOMA_SHOW_MEDIAPLAYER'=>(get_sys_pref('TOMA_SHOW_MEDIAPLAYER', 'YES')=='YES'),
                                                                          'TOMA_SHOW_REALPLAYER'=>(get_sys_pref('TOMA_SHOW_REALPLAYER', 'NO')=='YES'),
                                                                          'TOMA_SHOW_WINAMP'=>(get_sys_pref('TOMA_SHOW_WINAMP', 'NO')=='YES')),false,true);
  form_label(str('TOMA_MEDIA_TYPES_PROMPT'));
  form_submit(str('SAVE_SETTINGS'),2);
  form_end();

  echo '<p>'.str('TOMA_UPDATE_CHANNELS_TEXT');

  form_start('index.php');
  form_hidden('section', 'INTERNET_TV');
  form_hidden('action', 'TOMA_CHANNELS');
  form_submit(str('TOMA_UPDATE_CHANNELS'),2,'left',240);
  form_end();
}

// ----------------------------------------------------------------------------------
// Saves the TOMA settings
// ----------------------------------------------------------------------------------

function internet_tv_toma_update()
{
  if (is_array($_REQUEST['media']) && count($_REQUEST['media'] >0))
  {
    // Disable all media types
    set_sys_pref('TOMA_SHOW_MEDIAPLAYER', 'NO');
    set_sys_pref('TOMA_SHOW_REALPLAYER', 'NO');
    set_sys_pref('TOMA_SHOW_WINAMP', 'NO');

    // Enable selected media types
    foreach ($_REQUEST['media'] as $media_type=>$value)
      set_sys_pref($media_type, 'YES');
  }

  if (!is_array($_REQUEST['media']))
    internet_tv_display('', "!".str('TOMA_TV_MEDIA_FAIL'));
  else
    internet_tv_display('', str('TOMA_TV_UPDATE_OK'));
}

// ----------------------------------------------------------------------------------
// Updates the TOMA channels list
// ----------------------------------------------------------------------------------

function internet_tv_toma_channels()
{
  // Ensure 24 hours have elapsed since last update
  if (time() < (get_sys_pref('TOMA_CHANNELS_UPDATE_TIME',0)+86400))
    internet_tv_display('', "!".str('TOMA_TV_CHANNELS_TIME_FAIL',number_format((time() - get_sys_pref('TOMA_CHANNELS_UPDATE_TIME'))/3600,2)));
  else
  {
    // Update the channel list
    $num_channels = update_channel_list();
    if (!empty($num_channels))
    {
      set_sys_pref('TOMA_CHANNELS_UPDATE_TIME',time());
      internet_tv_display('', str('TOMA_TV_CHANNELS_OK', $num_channels));
    }
    else
      internet_tv_display('', "!".str('TOMA_TV_CHANNELS_FAIL'));
  }
}

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
