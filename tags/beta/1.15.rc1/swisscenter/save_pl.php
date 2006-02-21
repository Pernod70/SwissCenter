<?
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor
 *************************************************************************************************/

  require_once( realpath(dirname(__FILE__).'/base/page.php'));
  require_once( realpath(dirname(__FILE__).'/base/utils.php'));
  require_once( realpath(dirname(__FILE__).'/base/az_picker.php'));
  require_once( realpath(dirname(__FILE__).'/base/playlist.php'));

  if (isset($_REQUEST["filename"]) && !empty($_REQUEST["filename"]))
  {
    // The user has selected a filename... so use it to save the file
  }
  else 
  {
    // Display the keyboard to allow the user to select a name.
    $case      = $_REQUEST["case"];
    $menu      = new menu();
    $search    = $_REQUEST["search"];
    $this_url  = 'save_pl.php';
    $base_dir  = str_suffix(get_sys_pref("playlists"),'/');
    
    // Automatically change case to make it easier for the user.
    if (empty($case) && strlen($search) == 1)
      $case = 'L';
      
    page_header(str('PLAYLIST_SAVE'), '','', (empty($_REQUEST["last"]) ? 'KEY_SPC' : $_REQUEST["last"] ) );
    echo '<table border=0 width="100%" height="'.convert_y(650).'"><tr><td width="'.convert_x(320).'" valign="top">';
    show_picker( $this_url.'?case='.$case.'&search=', $search, $case);
    echo '</td><td valign=top>';
      
    echo str('PLAYLIST_SAVE_PROMPT');
    echo '<p><center>&gt; &nbsp; '.$search.' &nbsp; &lt;</center>';
    
    if ( file_exists($base_dir.$search.'.m3u') )
      echo '<p>'.str('FILE_EXISTS').'';
  
    if (strlen($search)>0)
      $menu->add_item(str('PLAYLIST_NAME_USE'),'manage_pl.php?save='.rawurlencode($base_dir.$search.'.m3u'));
      
    $menu->add_item(str('PLAYLIST_NAME_CANCEL'),'manage_pl.php');
    $menu->display(300);
      
    echo '</td></tr></table>';
  
    // Display the appropriate ABC buttons.
    $buttons[] = array('text'=>str('CLEAR_NAME'), 'url'=>$this_url);
  
    if ($case == "L")
      $buttons[] = array('text'=>str('UPPERCASE'), 'url'=>$this_url.'?case=U&search='.$search);
    else
      $buttons[] = array('text'=>str('LOWERCASE'), 'url'=>$this_url.'?case=L&search='.$search);
    
    page_footer('manage_pl.php',$buttons);
  }

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
