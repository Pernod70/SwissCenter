<?
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor
 *************************************************************************************************/

  require_once("base/page.php");
  require_once("base/utils.php");
  require_once('base/az_picker.php');

  page_header(str('PLAYLIST_SAVE'), '','','', (empty($_REQUEST["last"]) ? 'KEY_SPC' : $_REQUEST["last"] ) );

  $menu      = new menu();
  $search    = $_REQUEST["search"];
  $case      = $_REQUEST["case"];
  $this_url  = 'save_pl.php';
  $base_dir  = str_suffix(get_sys_pref("playlists"),'/');

  echo '<table border=0 height="320px" width="100%"><tr><td width="200px" valign="top">';
  show_picker( $this_url.'?case='.$case.'&search=', $search, $case);
  echo '</td><td valign=top>';

  echo str('PLAYLIST_SAVE_PROMPT').

  $menu->add_item($search,'manage_pl.php?save='.rawurlencode($base_dir.$search.'.m3u'));
  $menu->display(300);
  
  if ( file_exists($base_dir.$search.'.m3u') )
    echo '<p>'.str('FILE_EXISTS').'';

  echo '</td></tr></table>';

  // Display the appropriate ABC buttons.
  $buttons[] = array('text'=>str('CLEAR_NAME'), 'url'=>$this_url);

  if ($case == "L")
    $buttons[] = array('text'=>str('UPPERCASE'), 'url'=>$this_url.'?case=U&search='.$search);
  else
    $buttons[] = array('text'=>str('LOWERCASE'), 'url'=>$this_url.'?case=L&search='.$search);
  
  page_footer('manage_pl.php',$buttons);

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
