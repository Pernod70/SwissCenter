<?php
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor
 *************************************************************************************************/

  require_once( realpath(dirname(__FILE__).'/base/page.php'));
  require_once( realpath(dirname(__FILE__).'/base/utils.php'));
  require_once( realpath(dirname(__FILE__).'/base/playlist.php'));

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
  $play_name  = $_SESSION["playlist_name"];
  $menu       = new menu();

  // Clean the current url in the history
  page_hist_current_update(url_remove_params(current_url(), array('clear', 'save')), '');

  if ($num_tracks > 0 )
  {
    $menu->add_item(str('PLAY'), play_playlist());
    $menu->add_item(str('PLAYLIST_CLEAR'), 'manage_pl.php?clear=Y&hist='.PAGE_HISTORY_REPLACE);
    $menu->add_item(str('PLAYLIST_EDIT'), 'edit_pl.php',true);
    $menu->add_item(str('PLAYLIST_LOAD_NEW'), 'load_pl.php?action=replace', true);
    $menu->add_item(str('PLAYLIST_APPEND'), 'load_pl.php?action=append', true);
    $menu->add_item(str('PLAYLIST_SAVE_CURRENT'), 'save_pl.php', true);
    if (is_user_admin())
      $menu->add_item( str('PLAYLIST_DELETE'), 'delete_pl.php', true);
  }
  else
  {
    $menu->add_item(str('PLAYLIST_LOAD_NEW'), 'load_pl.php?action=replace',true);
    if (is_user_admin())
      $menu->add_item(str('PLAYLIST_DELETE'), 'delete_pl.php?DIR=', true);
  }

  // Is there a picture for us to display?
  $pl_img = file_albumart(get_sys_pref('PLAYLISTS', SC_LOCATION.'playlists').'/'.$play_name.'.m3u', false);

  if ( empty($pl_img) )
  {
    page_header( str('MANAGE_PLAYLISTS'), '','',1,false,'','PAGE_PLAYLISTS' );
    $pl_img = SC_LOCATION.'images/dot.gif';
  }
  else
  {
    page_header( str('MANAGE_PLAYLISTS') );
  }

  pl_info();

  echo '<table width="100%" cellpadding="0" cellspacing="0" border="0">
        <tr><td valign="top" width="'.convert_x(280).'" align="center">
            <table width="100%">
              <tr><td height="'.convert_y(10).'"></td></tr>
              <tr><td valign="top"><center>'.img_gen($pl_img,280,550).'</center></td></tr>
            </table></td>
            <td valign="top">';

  $menu->display(1, style_value("MENU_PLAYLISTS_WIDTH"), style_value("MENU_PLAYLISTS_ALIGN"));

  echo '</td></tr></table>';

  // Buttons (Shuffle on/off)
  $buttons = array();
  if (!isset($_SESSION["shuffle"]) || $_SESSION["shuffle"] == 'off')
    $buttons[] = array('text'=>str('SHUFFLE_ON'), 'url'=>url_add_params('manage_pl.php', array('shuffle'=>'on', 'hist'=>PAGE_HISTORY_REPLACE)));
  else
    $buttons[] = array('text'=>str('SHUFFLE_OFF'), 'url'=>url_add_params('manage_pl.php', array('shuffle'=>'off', 'hist'=>PAGE_HISTORY_REPLACE)));

  page_footer( 'index.php', $buttons );

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
