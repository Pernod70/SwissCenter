<?
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor
 *************************************************************************************************/

  require_once("base/page.php");
  require_once("base/utils.php");
  require_once('base/az_picker.php');

  page_header('Save Playlist', '', (empty($_REQUEST["last"]) ? 'KEY_SPC' : $_REQUEST["last"] ) );

  $menu      = new menu();
  $search    = $_REQUEST["search"];
  $case      = $_REQUEST["case"];
  $this_url  = 'save_pl.php';
  $base_dir  = str_suffix($_SESSION["opts"]["playlists"],'/');

  echo '<table border=0 height="320px" width="100%"><tr><td width="200px" valign="top">';
  show_picker( $this_url.'?case='.$case.'&search=', $search, $case);
  echo '</td><td valign=top>';

  echo 'Please enter the name you would like to use for your playlist'.

  $menu->add_item($search,'manage_pl.php?save='.rawurlencode($base_dir.$search.'.m3u'));
  $menu->display(300);
  
  if ( file_exists($base_dir.$search.'.m3u') )
    echo '<p>WARNING: A file with this name alreay exists. If you save now it will overwrite the existing file.';

  echo '</td></tr></table>';

  // Display the appropriate ABC buttons.
  $buttons[] = array('text'=>'Clear Name', 'url'=>$this_url);

  if ($case == "L")
    $buttons[] = array('text'=>'Uppercase', 'url'=>$this_url.'?case=U&search='.$search);
  else
    $buttons[] = array('text'=>'Lowercase', 'url'=>$this_url.'?case=L&search='.$search);
  
  page_footer('manage_pl.php',$buttons);

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
