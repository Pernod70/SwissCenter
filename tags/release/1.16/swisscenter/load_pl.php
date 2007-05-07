<?
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor
 *************************************************************************************************/

  require_once( realpath(dirname(__FILE__).'/base/page.php'));
  require_once( realpath(dirname(__FILE__).'/base/utils.php'));
  require_once( realpath(dirname(__FILE__).'/base/browse.php'));
  require_once( realpath(dirname(__FILE__).'/base/playlist.php'));

  function output_link( $file )
  {
    return 'load_pl.php?load='.rawurlencode($file).'&action='.$_REQUEST["action"];
  }

  // Load a playlist (Overwriting or appending to the existing playlist)  
  if ( isset($_REQUEST["load"]) && !empty($_REQUEST["load"]))
  {
    load_pl(rawurldecode($_REQUEST["load"]),$_REQUEST["action"]);
    page_inform(2,"manage_pl.php",str('PLAYLIST_LOAD'),str('PLAYLIST_LOAD_OK'));
  }
  else 
  {
    browse_fs( str('PLAYLIST_LOAD')
             , get_sys_pref("playlists")
             , 'manage_pl.php'
             , array('m3u')
             );
  }

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
