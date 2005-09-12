<?
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor
 *************************************************************************************************/

  require_once("base/page.php");
  require_once("base/utils.php");
  require_once('base/browse.php');
  require_once("base/playlist.php");

  function output_link( $file )
  {
    return 'load_pl.php?action='.$_REQUEST["action"].'&load='.rawurlencode($file);
  }

  // Load a playlist (Overwriting or appending to the existing playlist)  
  if ( isset($_REQUEST["load"]) && !empty($_REQUEST["load"]))
  {
    load_pl(rawurldecode($_REQUEST["load"]),$_REQUEST["action"]);
    page_inform(2,"manage_pl.php",str('PLAYLIST_LOAD'),str('PLAYLIST_LOAD_OK'));
  }
  else 
  {
    browse_fs(str('PLAYLIST_LOAD'), get_sys_pref("playlists"), 'manage_pl.php', array('m3u') );
  }

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
