<?
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor
 *************************************************************************************************/

  require_once("base/page.php");
  require_once("base/utils.php");
  require_once("base/playlist.php");

  page_header( str('MANAGE_PLAYLISTS'));
  $buttons = array();

  //---------------------------------------------------------------------------------------
  // Process any actions passed on the query string
  //---------------------------------------------------------------------------------------

  // Save the Playlist
  if ( isset($_REQUEST["save"]) && !empty($_REQUEST["save"]))
    save_pl($_REQUEST['save']);

  if ( isset($_REQUEST["clear"]) && !empty($_REQUEST["clear"]))
    clear_pl();

  // Clear the Playlist

  if ( isset($_REQUEST["clear"]) && !empty($_REQUEST["clear"]))
    clear_pl();

  // Turn shuffle on/off.

  if (isset($_REQUEST["shuffle"]))
    $_SESSION["shuffle"] = $_REQUEST["shuffle"];

  //---------------------------------------------------------------------------------------
  // Output the page
  //---------------------------------------------------------------------------------------

  $num_tracks = count($_SESSION["playlist"]);
  $menu       = new menu();

  if ($num_tracks > 0 )
  {
    $menu->add_item(str('PLAY'),pl_link('playlist'));
    $menu->add_item(str('PLAYLIST_CLEAR'),'manage_pl.php?clear=Y');
    $menu->add_item(str('PLAYLIST_EDIT'),'edit_pl.php',true);
    $menu->add_item(str('PLAYLIST_LOAD_NEW'),'load_pl.php?action=replace', true);
    $menu->add_item(str('PLAYLIST_APPEND'),'load_pl.php?action=append', true);
    $menu->add_item( str('PLAYLIST_SAVE_CURRENT'),'save_pl.php', true);
  }
  else
  {
    $menu->add_item(str('PLAYLIST_LOAD_NEW'),'load_pl.php?action=replace',true);
  }

  // Buttons (Shuffle on/off)
  if (!isset($_SESSION["shuffle"]) || $_SESSION["shuffle"] == 'off')
    $buttons[] = array('text'=>str('SHUFFLE_ON'), 'url'=>'manage_pl.php?shuffle=on');
  else
    $buttons[] = array('text'=>str('SHUFFLE_OFF'), 'url'=>'manage_pl.php?shuffle=off');

  pl_info();
  $menu->display();
  page_footer( 'index.php', $buttons );
  
/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
