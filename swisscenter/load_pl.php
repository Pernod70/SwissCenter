<?php
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor
 *************************************************************************************************/

  require_once( realpath(dirname(__FILE__).'/base/page.php'));
  require_once( realpath(dirname(__FILE__).'/base/utils.php'));
  require_once( realpath(dirname(__FILE__).'/base/browse.php'));
  require_once( realpath(dirname(__FILE__).'/base/playlist.php'));

  function output_link( $file )
  {
    return 'load_pl.php?load='.rawurlencode($file).'&action='.$_REQUEST["action"].'&hist='.PAGE_HISTORY_REPLACE;
  }

  $fsp    = rawurldecode($_REQUEST["load"]);
  $action = $_REQUEST["action"];
  $name   = file_noext(basename($fsp));
  $custom = "&lt;".str('CUSTOM')."&gt;";

  // Load a playlist (Overwriting or appending to the existing playlist)
  if (!empty($fsp))
  {
    $tracks = load_pl($fsp, $failed);

    // Either replace the existing playlist or merge the two together.
    if ($action == 'replace')
      set_current_playlist($name, $tracks);
    else
      set_current_playlist( $custom, array_merge($_SESSION["playlist"], $tracks) );

    if (count($failed) > 0)
      page_inform(5, page_hist_previous(), str('PLAYLIST_LOAD'), str('PLAYLIST_LOAD_FAIL', count($tracks), count($failed)).
                                        '<p>'.implode('<br>', array_slice($failed,0,8)));
    else
      page_inform(2, page_hist_previous(), str('PLAYLIST_LOAD'), str('PLAYLIST_LOAD_OK'));
  }
  else
  {
    browse_fs( str('PLAYLIST_LOAD')
             , get_sys_pref("playlists")
             , page_hist_previous()
             , media_exts_playlists()
             );
  }

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
