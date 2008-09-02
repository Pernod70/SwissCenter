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

  $fsp    = rawurldecode($_REQUEST["playlist"]);
  $name   = file_noext(basename($fsp));
  
  if ( isset($_REQUEST["del"]) && ($_REQUEST["del"] == 'Y') )
  {
    // Delete the playlist file
    send_to_log(8, "Deleting playlist: $fsp");
    unlink($fsp);
    page_inform(2,'manage_pl.php','',str('PLAYLIST_DELETED'));
  }
  elseif (!empty($fsp))
  {
    // Delete options
    page_header( str('PLAYLIST_DELETE_CONFIRM'), $name );
    $menu = new menu();
    $menu->add_item( str('YES'), url_add_param(current_url(),'del','Y') );
    $menu->add_item( str('NO'), 'manage_pl.php' );
    $menu->display();
    page_footer('manage_pl.php');
  }
  else
  {
    browse_fs( str('PLAYLIST_DELETE')
             , get_sys_pref("playlists")
             , 'manage_pl.php'
             , media_exts_playlists()
             );
  }
  
/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
