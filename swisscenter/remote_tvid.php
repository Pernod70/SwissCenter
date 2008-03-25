<?php
/**************************************************************************************************
   SWISScenter Source                                                              Nigel Barnes
 *************************************************************************************************/
  
  require_once( realpath(dirname(__FILE__).'/base/capabilities.php'));
  require_once( realpath(dirname(__FILE__).'/base/page.php'));
  require_once( realpath(dirname(__FILE__).'/base/prefs.php'));

  $tvid_sc = array('QUICK_MUSIC'=>'MUSIC',
                   'QUICK_MOVIE'=>'MOVIE',
                   'QUICK_PHOTO'=>'PHOTO',
                   'QUICK_HOME' =>'HOME',
                   'QUICK_KEY_A'=>'KEY_A',
                   'QUICK_KEY_B'=>'KEY_B',
                   'QUICK_KEY_C'=>'KEY_C',
                   'BACKSPACE'  =>'BACKSPACE');
//                   'PGUP'       =>'PGUP',
//                   'PGDN'       =>'PGDN');

  // The following is a list of all known possible TVID codes that are recognised 
  $tvid_list     = array('MUSIC',
                         'MOVIE',
                         'PHOTO',
                         'RED',
                         'GREEN',
                         'BLUE',
                         'YELLOW',
                         'A',
                         'B',
                         'C',
                         'KEY_A',
                         'KEY_B',
                         'KEY_C',
                         'CLEAR',
                         'ENTER',
                         'EPG',
                         'ESCAPE',
                         'ESC',
                         'GOTO',
                         'HELP',
                         'MUTE',
                         'PIP',
                         'REC',
                         'REFRESH',
                         'REPEAT',
                         'RESET',
                         'RL',
                         'SETUP',
                         'TAB',
                         'TT',
                         'URL',
                         'USER',
                         'USR',
                         'VOUT',
                         'ZOOM',
                         'FAST_FORWARD',
                         'FAST_BACKWARD',
                         'NEXT_TRACK',
                         'PREVIOUS_TRACK',
                         'NEXT',
                         'PREV',
                         'PLAY',
                         'PLAYPAUSE',
                         'STOP',
                         'BACKSPACE',
                         'BACK',
                         'PAGE_UP',
                         'PGUP',
                         'PAGE_DOWN',
                         'PGDN');

  page_header( str('SETUP_REMOTE').' ('.get_player_type().')', str('REMOTE_PROMPT') );
  
  if (isset($_REQUEST["tvid"]))
  {
    // Remote key recognised so save it to the tvid_prefs
    set_tvid_pref(get_player_type(), $tvid_sc[$_REQUEST["tvid_sel"]], $_REQUEST["tvid"]);
  }
  elseif (isset($_REQUEST["func"]))
  { 
    // Setup hidden links for all recognised tvid codes
    foreach ($tvid_list as $tvid)
      echo '<a href="remote_tvid.php?tvid='.$tvid.'&tvid_sel='.$_REQUEST["func"].'" tvid="'.$tvid.'"></a>';
  }             
  elseif (isset($_REQUEST["default"]) && $_REQUEST["default"] == 'Y')
  {
    // Reset all tvid codes to the hardcoded defaults
    db_sqlcommand("update tvid_prefs set tvid_custom=null where player_type='".get_player_type()."'",false);
  }
  
  // Show the current configuration
  $menu = new menu();
  
  foreach (array_keys($tvid_sc) as $tvid_key)
    $menu->add_item(str($tvid_key).' -> ['.get_tvid_pref(get_player_type(), $tvid_sc[$tvid_key]).']', url_set_param('remote_tvid.php','func',$tvid_key));
  
  // Add menu options to reset values to default, and to exit
  $menu->add_item(str('TVID_RESET_DEFAULT'), url_set_param('remote_tvid.php','default','Y'));
  $menu->add_item(str('RETURN_MAIN_MENU'), 'config.php');
  
  $menu->display();
  
  // Function selected so prompt to press remote key to map
  if (isset($_REQUEST["func"]))
  {
    echo '<center>'.str('PRESS_KEY_NOW', str($_REQUEST["func"])).'</center>';
  }
  
  page_footer( '','',0,false );

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
