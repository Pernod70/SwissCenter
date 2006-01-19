<?
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor
 *************************************************************************************************/
    
  // ----------------------------------------------------------------------------------
  // Displays the various connectivity options
  // ----------------------------------------------------------------------------------

  function connect_display($message = '')
  {
    $list = array( str('ENABLED')=>'YES',str('DISABLED')=>'NO');
    
    echo "<h1>".str('CONNECT_TITLE')."</h1>";
    message($message);    
    echo '<p>'.str('CONNECT_TEXT');

    form_start('index.php', 150, 'conn');
    form_hidden('section', 'CONNECT');
    form_hidden('action', 'MODIFY');

    form_radio_static('radio',str('INTERNET_RADIO'),$list,get_sys_pref('radio_enabled','YES'),false,true);
    form_label(str('INTERNET_RADIO_PROMPT'));

    form_radio_static('weather',str('WEATHER_FORECAST'),$list,get_sys_pref('weather_enabled','YES'),false,true);
    form_label(str('WEATHER_FORECAST_PROMPT', '<a href="http://www.weather.com">'.str('WEATHER_CHANNEL').'</a>'));
    
    form_radio_static('update',str('UPDATE_CHECK'),$list,get_sys_pref('updates_enabled','YES'),false,true);
    form_label(str('UPDATE_CHECK_PROMPT','<a href="http://www.swisscenter.co.uk">Swisscenter.co.uk</a>'));

    form_radio_static('messages',str('NEW_MESSAGES'),$list,get_sys_pref('messages_enabled','YES'),false,true);
    form_label(str('NEW_MESSAGES_PROMPT'));

    form_submit(str('SAVE_SETTINGS'), 2);
    form_end();
  }
  
  // ----------------------------------------------------------------------------------
  // Saves the new parameters
  // ----------------------------------------------------------------------------------
  
  function connect_modify()
  {
    set_sys_pref('radio_enabled',$_REQUEST["radio"]);
    set_sys_pref('weather_enabled',$_REQUEST["weather"]);
    set_sys_pref('updates_enabled',$_REQUEST["update"]);
    set_sys_pref('messages_enabled',$_REQUEST["messages"]);
    connect_display(str('SAVE_SETTINGS_OK'));
  }
  
/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>