<?
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor
 *************************************************************************************************/

  require_once("base/page.php");
  require_once("base/utils.php");
  require_once("base/playlist.php");

  page_header( "Manage Playlists" );
  $buttons = array();

  //---------------------------------------------------------------------------------------
  // Process any actions passed on the query string
  //---------------------------------------------------------------------------------------

  // Load a playlist (Overwriting or appending to the existing playlist)
  
  if ( isset($_REQUEST["load"]) && !empty($_REQUEST["load"]))
    load_pl(rawurldecode($_REQUEST["load"]),$_REQUEST["action"]);

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
    $menu->add_item('Play',pl_link('playlist'));
    $menu->add_item('Clear the Playlist','manage_pl.php?clear=Y');
    $menu->add_item('Edit the Playlist','edit_pl.php',true);
    $menu->add_item( 'Load a New playlist','load_pl.php?action=replace', true);
    $menu->add_item( 'Append Another Playlist','load_pl.php?action=append', true);
    $menu->add_item( 'Save the Current Playlist','save_pl.php', true);
  }
  else
  {
    $menu->add_item('Load a Playlist','load_pl.php?action=replace',true);
  }

  // Buttons (Shuffle on/off)
  if (!isset($_SESSION["shuffle"]) || $_SESSION["shuffle"] == 'off')
    $buttons[] = array('text'=>'Turn Shuffle On', 'url'=>'manage_pl.php?shuffle=on');
  else
    $buttons[] = array('text'=>'Turn Shuffle Off', 'url'=>'manage_pl.php?shuffle=off');

  pl_info();
  $menu->display();
  page_footer( 'index.php', $buttons );
  
/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
