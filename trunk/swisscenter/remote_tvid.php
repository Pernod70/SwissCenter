<?php
/**************************************************************************************************
   SWISScenter Source                                                              Nigel Barnes
 *************************************************************************************************/
  
  require_once( realpath(dirname(__FILE__).'/base/capabilities.php'));
  require_once( realpath(dirname(__FILE__).'/base/page.php'));
  require_once( realpath(dirname(__FILE__).'/base/prefs.php'));

  function trim_tvid($tvid)
  {
    return substr($tvid,7,strlen($tvid)-9);
  }

  $tvid_default  = array('QUICK_MUSIC'=>'MUSIC',
                         'QUICK_MOVIE'=>'MOVIE',
                         'QUICK_PHOTO'=>'PHOTO',
                         'QUICK_HOME' =>'HOME',
                         'QUICK_KEY_A'=>'KEY_A',
                         'QUICK_KEY_B'=>'KEY_B',
                         'QUICK_KEY_C'=>'KEY_C',
                         'BACKSPACE'  =>'BACKSPACE');
//                         'PGUP'       =>'PGUP',
//                         'PGDN'       =>'PGDN');

  // The following is a list of all known possible TVID codes that are recognised 
  $tvid_list     = array(
                         'MUSIC',
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
                         'REPEAT',
                         'RESET',
                         'RL',
                         'SETUP',
                         'TAB',
                         'TT',
                         'USER',
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
    // Remote key recognised so save it to the system_prefs
    set_sys_pref('TVID_'.get_player_type().'_'.strtoupper($tvid_default[$_REQUEST["tvid_sel"]]), strtoupper($_REQUEST["tvid"]));
  }
  elseif (isset($_REQUEST["func"]))
  { 
    // Setup hidden links for all recognised tvid codes
    foreach ($tvid_list as $tvid)
      echo '<a href="remote_tvid.php?tvid='.$tvid.'&tvid_sel='.$_REQUEST["func"].'" tvid="'.$tvid.'"></a>';
  }             
  elseif (isset($_REQUEST["default"]) && strtoupper($_REQUEST["default"]) == 'Y')
  {
    // Reset all tvid codes to the hardcoded defaults
    db_sqlcommand("delete from system_prefs where name like 'TVID_".get_player_type()."_%'",false);
  }
  
  // Show the current configuration
  $menu = new menu();
  
  foreach (array_keys($tvid_default) as $tvid_key)
    $menu->add_item(str($tvid_key).' -> ['.strtoupper(get_sys_pref('TVID_'.get_player_type().'_'.$tvid_default[$tvid_key], trim_tvid(tvid($tvid_default[$tvid_key])))).']', url_set_param('remote_tvid.php','func',$tvid_key));
  
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
