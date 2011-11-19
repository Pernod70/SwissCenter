<?php
/**************************************************************************************************
   SWISScenter Source                                                              Nigel Barnes
 *************************************************************************************************/

  require_once( realpath(dirname(__FILE__).'/base/capabilities.php'));
  require_once( realpath(dirname(__FILE__).'/base/page.php'));
  require_once( realpath(dirname(__FILE__).'/base/prefs.php'));

  // All user definable button actions
  $tvid_sc = array('QUICK_MUSIC'=>'MUSIC',
                   'QUICK_MOVIE'=>'MOVIE',
                   'QUICK_PHOTO'=>'PHOTO',
                   'QUICK_HOME' =>'HOME',
                   'QUICK_KEY_A'=>'KEY_A',
                   'QUICK_KEY_B'=>'KEY_B',
                   'QUICK_KEY_C'=>'KEY_C',
                   'QUICK_KEY_D'=>'KEY_D',
                   'QUICK_ICON_1'=>'ICON_1',
                   'QUICK_ICON_2'=>'ICON_2',
                   'QUICK_ICON_3'=>'ICON_3',
                   'BACKSPACE'  =>'BACKSPACE',
                   'PGUP'       =>'PGUP',
                   'PGDN'       =>'PGDN');

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
                         'D',
                         'KEY_A',
                         'KEY_B',
                         'KEY_C',
                         'KEY_D',
                         'CLEAR',
                         'COPY',
                         'ENTER',
                         'EPG',
                         'ESCAPE',
                         'ESC',
                         'GOTO',
                         'HELP',
                         'INFO',
                         'MUTE',
                         'PASTE',
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
      echo '<a href="remote_tvid.php?tvid='.$tvid.'&tvid_sel='.$_REQUEST["func"].'&hist='.PAGE_HISTORY_REPLACE.'" tvid="'.$tvid.'"></a>';
  }
  elseif (isset($_REQUEST["default"]) && $_REQUEST["default"] == 'Y')
  {
    // Reset all tvid codes to the hardcoded defaults
    db_sqlcommand("update tvid_prefs set tvid_custom=null where player_type='".get_player_type()."'",false);
  }

  // Show the current configuration
  $page  = (isset($_REQUEST["page"]) ? $_REQUEST["page"] : 1);
  $start = ($page-1) * MAX_PER_PAGE;
  $end   = min($start+MAX_PER_PAGE,count($tvid_sc));
  $last_page  = ceil(count($tvid_sc)/(MAX_PER_PAGE));

  $menu = new menu();

  if (count($tvid_sc) > MAX_PER_PAGE)
  {
    $menu->add_up( url_add_param(current_url(),'page',($page > 1 ? ($page-1) : $last_page)) );
    $menu->add_down( url_add_param(current_url(),'page',($page < $last_page ? ($page+1) : 1)) );
  }

  $count = 0;
  foreach (array_keys($tvid_sc) as $tvid_key)
  {
    if ($count >= $start && $count < $end)
    {
      $menu->add_item(str($tvid_key).' -> ['.get_tvid_pref(get_player_type(), $tvid_sc[$tvid_key]).']', url_set_params('remote_tvid.php',array('func'=>$tvid_key, 'page'=>$page, 'hist'=>PAGE_HISTORY_REPLACE)));
    }
    $count++;
  }

  // Add menu options to reset values to default, and to exit
  $menu->add_item(str('TVID_RESET_DEFAULT'), url_set_params('remote_tvid.php', array('default'=>'Y', 'hist'=>PAGE_HISTORY_REPLACE)));
  $menu->add_item(str('RETURN_MAIN_MENU'), page_hist_previous());

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
