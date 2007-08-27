<?php
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor
 *************************************************************************************************/
    
  // ----------------------------------------------------------------------------------
  // Displays the various connectivity options
  // ----------------------------------------------------------------------------------

  function connect_display($message = '')
  {
    $setting_vals = array( str('ENABLED')=>'YES',str('DISABLED')=>'NO',str('AUTOMATIC')=>'AUTO');
    $option_vals  = array( str('ENABLED')=>'YES',str('DISABLED')=>'NO');
    
    echo "<h1>".str('CONNECT_TITLE')."</h1>";
    message($message);    
    echo '<p>'.str('CONNECT_TEXT');

    // Internet connectivity.
    
    form_start('index.php', 150, 'conn');
    form_hidden('section', 'CONNECT');
    form_hidden('action', 'SET_STATUS');
    form_radio_static('status',str('INTERNET_SETTING'),$setting_vals,get_sys_pref('internet_setting','AUTO'),false,true);
    form_label(str('INTERNET_SETTING_PROMPT'));
    form_submit(str('SAVE_SETTINGS'), 2);
    form_end();

    echo '<p>'.str('CONNECT_OPT_TEXT');

    // Individual settings
    
    form_start('index.php', 150, 'conn');
    form_hidden('section', 'CONNECT');
    form_hidden('action', 'SET_OPTS');

    form_radio_static('radio',str('INTERNET_RADIO'),$option_vals,get_sys_pref('radio_enabled','YES'),false,true);
    form_label(str('INTERNET_RADIO_PROMPT'));

    form_radio_static('web',str('BROWSE_WEB'),$option_vals,get_sys_pref('web_enabled','YES'),false,true);
    form_label(str('BROWSE_WEB_PROMPT'));
    
    form_radio_static('weather',str('WEATHER_FORECAST'),$option_vals,get_sys_pref('weather_enabled','YES'),false,true);
    form_label(str('WEATHER_FORECAST_PROMPT', '<a href="http://www.weather.com">'.str('WEATHER_CHANNEL').'</a>'));
    
    form_radio_static('wiki',str('WIKI_LOOKUP'),$option_vals,get_sys_pref('wikipedia_lookups','YES'),false,true);
    form_label(str('WIKI_LOOKUP_PROMPT'));
    
    form_radio_static('update',str('UPDATE_CHECK'),$option_vals,get_sys_pref('updates_enabled','YES'),false,true);
    form_label(str('UPDATE_CHECK_PROMPT','<a href="http://www.swisscenter.co.uk">Swisscenter.co.uk</a>'));

    form_radio_static('messages',str('NEW_MESSAGES'),$option_vals,get_sys_pref('messages_enabled','YES'),false,true);
    form_label(str('NEW_MESSAGES_PROMPT'));

    form_submit(str('SAVE_SETTINGS'), 2);
    form_end();
  }
  
  // ----------------------------------------------------------------------------------
  // Saves the new parameters
  // ----------------------------------------------------------------------------------
  
  function connect_set_status()
  {
    set_sys_pref('internet_setting',$_REQUEST["status"]);
    connect_display(str('SAVE_SETTINGS_OK'));    
  }
  
  // ----------------------------------------------------------------------------------
  // Saves the new parameters
  // ----------------------------------------------------------------------------------
  
  function connect_set_opts()
  {
    set_sys_pref('radio_enabled',$_REQUEST["radio"]);
    set_sys_pref('web_enabled',$_REQUEST["web"]);
    set_sys_pref('weather_enabled',$_REQUEST["weather"]);
    set_sys_pref('wikipedia_lookups',$_REQUEST["wiki"]);
    set_sys_pref('updates_enabled',$_REQUEST["update"]);
    set_sys_pref('messages_enabled',$_REQUEST["messages"]);
    connect_display(str('SAVE_SETTINGS_OK'));
  }
  
/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
