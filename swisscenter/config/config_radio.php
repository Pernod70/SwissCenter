<?php
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor
 *************************************************************************************************/
  
  // ----------------------------------------------------------------------------------
  // Display current config
  // ----------------------------------------------------------------------------------
  
  function radio_display( $message = '' )
  {
    echo '<p><h1>'.str('CONFIG_RADIO_OPTIONS').'<p>';
    message($message);
    form_start('index.php');
    form_hidden('section','RADIO');
    form_hidden('action','UPDATE');
    form_input('maxnum',str('IRADIO_MAX_STATIONS'),20,'2',get_sys_pref('iradio_max_stations',24));
    form_label(str('IRADIO_MAX_STATIONS_PROMPT'));
    form_input('cache_expire',str('IRADIO_CACHE_EXPIRE'),20,'',get_sys_pref('iradio_cache_expire',3600));
    form_label(str('IRADIO_CACHE_EXPIRE_PROMPT'));
    form_submit(str('SAVE_SETTINGS'),2);
    form_end();
  }
  
  // ----------------------------------------------------------------------------------
  // Saves the new parameter
  // ----------------------------------------------------------------------------------
  
  function radio_update()
  {
    $maxnum = (int) $_REQUEST["maxnum"];
    $cache_expire = (int) $_REQUEST["cache_expire"];
    if (empty($cache_expire)) $cache_expire = 0;
    
    if (empty($_REQUEST["maxnum"]))
      radio_display("!".str('IRADIO_ERROR_MAXNUM'));
    elseif (empty($maxnum))
      radio_display("!".str('IRADIO_ERROR_MAXNUM_ZERO'));
    elseif (empty($_REQUEST["cache_expire"]))
      radio_display("!".str('IRADIO_ERROR_CACHE_EXPIRE'));
    else 
    {
      set_sys_pref('iradio_max_stations',$maxnum);
      set_sys_pref('iradio_cache_expire',$cache_expire);
      radio_display(str('SAVE_SETTINGS_OK'));
    }
  }
  
/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
