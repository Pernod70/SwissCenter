<?php
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor
 *************************************************************************************************/

  require_once( realpath(dirname(__FILE__).'/base/page.php'));
  require_once( realpath(dirname(__FILE__).'/base/utils.php'));
  require_once( realpath(dirname(__FILE__).'/base/playlist.php'));

  page_header( str('MANAGE_PLAYLISTS'), '','',1,false,'',PAGE_PLAYLISTS);
  $buttons = array();

  //---------------------------------------------------------------------------------------
  // Process any actions passed on the query string
  //---------------------------------------------------------------------------------------

  // Save the Playlist
  if ( isset($_REQUEST["save"]) && !empty($_REQUEST["save"]))
    save_pl($_REQUEST['save']);

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
    $menu->add_item(str('PLAY'),play_playlist());
    $menu->add_item(str('PLAYLIST_CLEAR'),'manage_pl.php?clear=Y');
    $menu->add_item(str('PLAYLIST_EDIT'),'edit_pl.php',true);
    $menu->add_item(str('PLAYLIST_LOAD_NEW'),'load_pl.php?action=replace', true);
    $menu->add_item(str('PLAYLIST_APPEND'),'load_pl.php?action=append', true);
    $menu->add_item(str('PLAYLIST_SAVE_CURRENT'),'save_pl.php', true);
    if (is_user_admin())
      $menu->add_item( str('PLAYLIST_DELETE'),'delete_pl.php', true);
  }
  else
  {
    $menu->add_item(str('PLAYLIST_LOAD_NEW'),'load_pl.php?action=replace',true);
    if (is_user_admin())
      $menu->add_item(str('PLAYLIST_DELETE'),'delete_pl.php', true);
  }

  // Buttons (Shuffle on/off)
  if (!isset($_SESSION["shuffle"]) || $_SESSION["shuffle"] == 'off')
    $buttons[] = array('text'=>str('SHUFFLE_ON'), 'url'=>'manage_pl.php?shuffle=on');
  else
    $buttons[] = array('text'=>str('SHUFFLE_OFF'), 'url'=>'manage_pl.php?shuffle=off');

  pl_info();
  $menu->display(1, style_value("MENU_PLAYLISTS_WIDTH"), style_value("MENU_PLAYLISTS_ALIGN"));
  page_footer( 'index.php', $buttons );

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
