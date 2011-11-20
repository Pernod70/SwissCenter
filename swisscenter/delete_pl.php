<?php
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor
 *************************************************************************************************/

  require_once( realpath(dirname(__FILE__).'/base/page.php'));
  require_once( realpath(dirname(__FILE__).'/base/utils.php'));
  require_once( realpath(dirname(__FILE__).'/base/browse.php'));

  function output_link( $file )
  {
    return 'delete_pl.php?playlist='.rawurlencode($file).'&del=N';
  }

  $fsp  = rawurldecode($_REQUEST["playlist"]);
  $name = file_noext(basename($fsp));

  if ( isset($_REQUEST["del"]) && ($_REQUEST["del"] == 'Y') )
  {
    // Delete the playlist file
    send_to_log(8, "Deleting playlist: $fsp");
    unlink($fsp);
    page_inform(2, page_hist_previous(), '', str('PLAYLIST_DELETED'));
  }
  elseif (!empty($fsp))
  {
    // Delete options
    page_header( str('PLAYLIST_DELETE_CONFIRM'), $name );
    $menu = new menu();
    $menu->add_item( str('YES'), url_add_params(current_url(), array('del'=>'Y', 'hist'=>PAGE_HISTORY_REPLACE)) );
    $menu->add_item( str('NO'), page_hist_previous() );
    $menu->display();
    page_footer(page_hist_previous());
  }
  else
  {
    browse_fs( str('PLAYLIST_DELETE')
             , get_sys_pref("playlists")
             , page_hist_previous()
             , media_exts_playlists()
             );
  }

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
